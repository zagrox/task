#!/bin/bash

# Version GitHub Sync Script
# A script to reconstruct and synchronize version history in Git 
# based on version.json history

set -e  # Exit on error

# Get absolute path to the Laravel root directory
function get_laravel_root {
    # Get the directory where this script is located
    SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
    
    # Laravel root is one level up from scripts directory
    LARAVEL_ROOT="$( cd "$SCRIPT_DIR/.." && pwd )"
    
    echo "$LARAVEL_ROOT"
}

LARAVEL_ROOT=$(get_laravel_root)
VERSION_FILE="$LARAVEL_ROOT/version.json"
VERSION_MANAGER="$LARAVEL_ROOT/project-management/version-manager.sh"
VERSION_BACKUP="$LARAVEL_ROOT/backups/version.json.bak"

# Safeguard: Create backup of current version.json
mkdir -p "$LARAVEL_ROOT/backups"
echo "Creating backup of current version.json file..."
cp "$VERSION_FILE" "$VERSION_BACKUP"
echo "✓ Backup created at $VERSION_BACKUP"

# Setup trap to restore version.json if the script is interrupted
function cleanup {
    if [ -f "$VERSION_BACKUP" ]; then
        echo ""
        echo "Script interrupted or errored. Restoring original version.json..."
        cp "$VERSION_BACKUP" "$VERSION_FILE"
        echo "✓ Original version restored"
        
        # Also restore app.php if needed
        if [ -f "$LARAVEL_ROOT/backups/app.php.bak" ]; then
            cp "$LARAVEL_ROOT/backups/app.php.bak" "$LARAVEL_ROOT/config/app.php"
            echo "✓ Original app.php restored"
        fi
    fi
}

# Register the cleanup function to be called on script exit, interrupt, or error
trap cleanup EXIT INT TERM ERR

# Also backup app.php
cp "$LARAVEL_ROOT/config/app.php" "$LARAVEL_ROOT/backups/app.php.bak"

# Check if we're in a git repository
if [ ! -d "$LARAVEL_ROOT/.git" ]; then
    echo "Error: Not a git repository"
    exit 1
fi

# Check if version-manager.sh exists and is executable
if [ ! -x "$VERSION_MANAGER" ]; then
    echo "Error: version-manager.sh not found or not executable"
    echo "Making it executable..."
    chmod +x "$VERSION_MANAGER"
fi

# Check if jq is installed
if ! command -v jq &> /dev/null; then
    echo "Error: jq command not found. Please install jq to parse version.json"
    exit 1
fi

# Read versions from version.json
echo "Reading version history from version.json..."
# Use arrays compatible with macOS bash
VERSION_ARRAY=()
NOTES_ARRAY=()
readarray() {
    local array=$1
    local input=$2
    
    # Clear the array
    eval "$array=()"
    
    # Read each line into the array
    while IFS= read -r line; do
        eval "$array+=(\"\$line\")"
    done <<< "$input"
}

# Read versions and notes into arrays
readarray VERSION_ARRAY "$(jq -r '.history[] | .version' "$VERSION_FILE")"
readarray NOTES_ARRAY "$(jq -r '.history[] | .notes' "$VERSION_FILE")"

# Extract current version numbers
CURRENT_MAJOR=$(jq -r '.major' "$VERSION_FILE")
CURRENT_MINOR=$(jq -r '.minor' "$VERSION_FILE")
CURRENT_PATCH=$(jq -r '.patch' "$VERSION_FILE")
CURRENT_VERSION="$CURRENT_MAJOR.$CURRENT_MINOR.$CURRENT_PATCH"

echo "Current version is: $CURRENT_VERSION"

# Ensure arrays are in reverse order (oldest first)
VERSION_REVERSED=()
NOTES_REVERSED=()

for ((i=${#VERSION_ARRAY[@]}-1; i>=0; i--)); do
    VERSION_REVERSED+=("${VERSION_ARRAY[$i]}")
    NOTES_REVERSED+=("${NOTES_ARRAY[$i]}")
done

VERSION_ARRAY=("${VERSION_REVERSED[@]}")
NOTES_ARRAY=("${NOTES_REVERSED[@]}")

# Backup current branch
CURRENT_BRANCH=$(git rev-parse --abbrev-ref HEAD)

# Display plan
echo "=========================================================="
echo "SEQUENTIAL VERSION RECONSTRUCTION PLAN"
echo "=========================================================="
echo "This script will reconstruct version history in Git based on"
echo "the version.json file. The following versions will be processed:"
echo ""

for i in "${!VERSION_ARRAY[@]}"; do
    echo "Version ${VERSION_ARRAY[$i]}: ${NOTES_ARRAY[$i]}"
done

echo ""
echo "WARNING: This is an advanced operation that reconstructs Git history."
echo "Your version.json will be temporarily modified during this process"
echo "but will be restored to version $CURRENT_VERSION when complete or on error."
echo "=========================================================="
echo ""

# Ask for confirmation
read -p "Do you want to proceed with this plan? (y/n): " confirm
if [[ "$confirm" != "y" && "$confirm" != "Y" ]]; then
    echo "Operation canceled"
    # Clean up backup without restoring (since we didn't modify anything yet)
    rm -f "$VERSION_BACKUP" "$LARAVEL_ROOT/backups/app.php.bak"
    trap - EXIT INT TERM ERR  # Clear the trap
    exit 0
fi

# Check if there are uncommitted changes
if [[ -n $(git status --porcelain) ]]; then
    echo "You have uncommitted changes. Commit or stash them before proceeding."
    
    read -p "Would you like to commit all changes before proceeding? (y/n): " commit_changes
    if [[ "$commit_changes" == "y" || "$commit_changes" == "Y" ]]; then
        echo "Committing all changes with message 'Pre-version reconstruction snapshot'"
        git add .
        git commit -m "Pre-version reconstruction snapshot"
    else
        echo "Please commit or stash your changes manually, then run this script again."
        # Clean up backup without restoring (since we didn't modify anything yet)
        rm -f "$VERSION_BACKUP" "$LARAVEL_ROOT/backups/app.php.bak"
        trap - EXIT INT TERM ERR  # Clear the trap
        exit 1
    fi
fi

echo ""
echo "Starting version reconstruction process..."
echo "=========================================================="

# For each version, process the changes
version_parts() {
    IFS='.' read -r -a parts <<< "$1"
    echo "${parts[0]} ${parts[1]} ${parts[2]}"
}

prev_major=0
prev_minor=0
prev_patch=0

for i in "${!VERSION_ARRAY[@]}"; do
    version="${VERSION_ARRAY[$i]}"
    notes="${NOTES_ARRAY[$i]}"
    
    echo "Processing version $version: $notes"
    
    # Determine the version type (major, minor, patch)
    read -r major minor patch <<< "$(version_parts "$version")"
    
    if [ "$major" -gt "$prev_major" ]; then
        version_type="major"
    elif [ "$minor" -gt "$prev_minor" ]; then
        version_type="minor"
    else
        version_type="patch"
    fi
    
    prev_major=$major
    prev_minor=$minor
    prev_patch=$patch
    
    # Apply version changes
    echo "Creating version with type: $version_type"
    
    # For version 1.0.0, we need to set it explicitly
    if [ "$version" == "1.0.0" ]; then
        # Update version.json for initial version
        cat > "$VERSION_FILE" << EOF
{
  "major": 1,
  "minor": 0,
  "patch": 0,
  "history": [
    {
      "version": "1.0.0",
      "date": "$(date +%Y-%m-%d)",
      "notes": "$notes"
    }
  ],
  "previous_versions": []
}
EOF
        # Commit the initial version
        git add "$VERSION_FILE"
        git commit -m "Initial version 1.0.0: $notes"
        git tag -a "v1.0.0" -m "$notes"
        
        echo "✓ Created version v1.0.0"
    else
        # Use version-manager.sh to apply subsequent versions
        "$VERSION_MANAGER" update "$version_type" "--notes=$notes"
        
        echo "✓ Created version v$version"
    fi
    
    # Simulate changes for this version based on the notes
    simulated_changes "$version" "$notes"
    
    echo "Completed processing version $version"
    echo "----------------------------------------------------------"
done

# IMPORTANT: After completing all versions, restore the original version.json
# This is a safeguard in case we didn't process all versions up to the current one
if [ -f "$VERSION_BACKUP" ]; then
    # Check if we processed all versions including the current one
    LAST_PROCESSED="${VERSION_ARRAY[${#VERSION_ARRAY[@]}-1]}"
    if [ "$LAST_PROCESSED" != "$CURRENT_VERSION" ]; then
        echo "Restoring original version.json with version $CURRENT_VERSION..."
        cp "$VERSION_BACKUP" "$VERSION_FILE"
        # Also update app.php
        cp "$LARAVEL_ROOT/backups/app.php.bak" "$LARAVEL_ROOT/config/app.php"
        echo "✓ Original version restored"
    fi
    
    # Remove backup files
    rm -f "$VERSION_BACKUP" "$LARAVEL_ROOT/backups/app.php.bak"
fi

# Clear the cleanup trap since we've handled restoration manually
trap - EXIT INT TERM ERR

echo "Version reconstruction completed successfully!"
echo "All versions from version.json have been applied to Git history."
echo ""
echo "Don't forget to push tags and commits to GitHub with:"
echo "git push origin --tags"
echo "git push origin $CURRENT_BRANCH"

# Function to simulate changes based on version and notes
simulated_changes() {
    local version="$1"
    local notes="$2"
    
    case "$version" in
        "1.0.0")
            # Initial release - Add core files
            mkdir -p app/Http/Controllers resources/views/tasks
            touch app/Http/Controllers/TaskController.php
            touch resources/views/tasks/layout.blade.php
            touch resources/views/tasks/index.blade.php
            
            git add app/Http/Controllers resources/views
            git commit -m "Initial task management structure for v1.0.0"
            ;;
            
        "1.0.1")
            # UI bugs and workflow improvements
            if [ -f "resources/views/tasks/layout.blade.php" ]; then
                echo "<!-- UI improvements for v1.0.1 -->" >> resources/views/tasks/layout.blade.php
            fi
            
            git add resources/views
            git commit -m "UI improvements for task creation workflow v1.0.1"
            ;;
            
        "1.0.2")
            # Git integration and task metrics
            touch app/Http/Controllers/TaskMetricsController.php
            touch resources/views/tasks/metrics.blade.php
            
            git add app/Http/Controllers resources/views
            git commit -m "Added task metrics dashboard for v1.0.2"
            ;;
            
        "1.0.3")
            # Task export and edit interface
            touch resources/views/tasks/edit.blade.php
            touch app/Http/Controllers/TaskExportController.php
            
            git add resources/views app/Http/Controllers
            git commit -m "Task export functionality and improved edit interface for v1.0.3"
            ;;
            
        "1.0.4")
            # Task filtering and reports
            touch resources/views/tasks/report.blade.php
            
            git add resources/views
            git commit -m "Implemented task filtering and reporting for v1.0.4"
            ;;
            
        "1.0.5")
            # Task management system and rollback
            touch scripts/rollback.sh
            chmod +x scripts/rollback.sh
            
            git add scripts
            git commit -m "Added rollback functionality for v1.0.5"
            ;;
            
        "1.0.6")
            # Fix render issues on dashboard
            if [ -f "resources/views/tasks/index.blade.php" ]; then
                echo "<!-- Fixed rendering issues for v1.0.6 -->" >> resources/views/tasks/index.blade.php
            fi
            
            git add resources/views
            git commit -m "Fixed task dashboard rendering issues for v1.0.6"
            ;;
            
        "1.0.7")
            # Version management tab
            touch app/Http/Controllers/VersionController.php
            touch resources/views/tasks/versions.blade.php
            touch project-management/version-manager.sh
            chmod +x project-management/version-manager.sh
            
            git add app/Http/Controllers resources/views project-management
            git commit -m "Added version management functionality for v1.0.7"
            ;;
            
        *)
            echo "No specific changes simulated for version $version"
            ;;
    esac
} 