#!/bin/bash

# MailZila Version Manager
# A shell script for managing version information

# Display script usage
function show_help {
    echo "MailZila Version Manager"
    echo "========================"
    echo "Description: Manage project versions, tags, and release notes"
    echo ""
    echo "Usage:"
    echo "  $0 [command] [options]"
    echo ""
    echo "Commands:"
    echo "  show                     Display current version information"
    echo "  update [type]            Update version number (patch, minor, major)"
    echo "  history                  Show version history"
    echo "  tag                      Create a git tag for the current version"
    echo "  push                     Push current version to repository"
    echo "  release [type] [notes]   Create a complete release (update, tag, and push)"
    echo "  help                     Display this help message"
    echo ""
    echo "Options:"
    echo "  --notes=\"[notes]\"        Specify release notes for version updates"
    echo "  --no-git                 Skip git operations for version updates"
    echo ""
    echo "Examples:"
    echo "  $0 show                               # Display current version"
    echo "  $0 update minor                       # Increment minor version"
    echo "  $0 update patch --notes=\"Bug fixes\"   # Update patch with notes"
    echo "  $0 release minor --notes=\"New feature: Task filtering\"  # Full release"
    echo ""
    exit 0
}

# Get absolute path to the Laravel root directory
function get_laravel_root {
    # Get the directory where this script is located
    SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
    
    # Laravel root is one level up from project-management directory
    LARAVEL_ROOT="$( cd "$SCRIPT_DIR/.." && pwd )"
    
    echo "$LARAVEL_ROOT"
}

# Get current version information
function get_version {
    LARAVEL_ROOT=$(get_laravel_root)
    VERSION_FILE="$LARAVEL_ROOT/version.json"
    
    if [ ! -f "$VERSION_FILE" ]; then
        echo "0.0.0"
        return
    fi
    
    if command -v jq &> /dev/null; then
        jq -r '"\(.major).\(.minor).\(.patch)"' "$VERSION_FILE"
    else
        # Fallback if jq not available
        grep -Eo '"major": *[0-9]+' "$VERSION_FILE" | grep -Eo '[0-9]+' | read MAJOR
        grep -Eo '"minor": *[0-9]+' "$VERSION_FILE" | grep -Eo '[0-9]+' | read MINOR
        grep -Eo '"patch": *[0-9]+' "$VERSION_FILE" | grep -Eo '[0-9]+' | read PATCH
        echo "$MAJOR.$MINOR.$PATCH"
    fi
}

# Display version information
function show_version {
    LARAVEL_ROOT=$(get_laravel_root)
    VERSION_FILE="$LARAVEL_ROOT/version.json"
    
    if [ ! -f "$VERSION_FILE" ]; then
        echo "Version file not found. Run 'php artisan version:update' to create it."
        exit 1
    fi
    
    echo "Current Version Information:"
    echo "----------------------------"
    
    if command -v jq &> /dev/null; then
        VERSION=$(jq -r '"\(.major).\(.minor).\(.patch)"' "$VERSION_FILE")
        echo "Version: $VERSION"
        
        if jq -e '.history[0]' "$VERSION_FILE" &> /dev/null; then
            echo "Release Date: $(jq -r '.history[0].date' "$VERSION_FILE")"
            echo "Release Notes: $(jq -r '.history[0].notes' "$VERSION_FILE")"
        fi
        
        # Show git status
        echo ""
        echo "Git Status:"
        echo "-----------"
        cd "$LARAVEL_ROOT"
        
        # Current branch
        BRANCH=$(git rev-parse --abbrev-ref HEAD 2>/dev/null)
        if [ $? -eq 0 ]; then
            echo "Current Branch: $BRANCH"
            
            # Check for uncommitted changes
            UNCOMMITTED=$(git status --porcelain 2>/dev/null)
            if [ -n "$UNCOMMITTED" ]; then
                echo "Uncommitted Changes: Yes ($(echo "$UNCOMMITTED" | wc -l | tr -d ' ') files)"
            else
                echo "Uncommitted Changes: No"
            fi
            
            # Check for unpushed commits
            UNPUSHED=$(git log origin/$BRANCH..$BRANCH --oneline 2>/dev/null)
            if [ -n "$UNPUSHED" ]; then
                echo "Unpushed Commits: Yes ($(echo "$UNPUSHED" | wc -l | tr -d ' ') commits)"
            else
                echo "Unpushed Commits: No"
            fi
        else
            echo "Not a git repository"
        fi
    else
        echo "jq command not found. Please install jq for better output."
        echo "Version file: $VERSION_FILE"
        cat "$VERSION_FILE"
    fi
}

# Show version history
function show_history {
    LARAVEL_ROOT=$(get_laravel_root)
    VERSION_FILE="$LARAVEL_ROOT/version.json"
    
    if [ ! -f "$VERSION_FILE" ]; then
        echo "Version file not found. Run 'php artisan version:update' to create it."
        exit 1
    fi
    
    echo "Version History:"
    echo "----------------"
    
    if command -v jq &> /dev/null; then
        # Check if history exists
        if ! jq -e '.history' "$VERSION_FILE" &> /dev/null; then
            echo "No version history found."
            exit 0
        fi
        
        jq -r '.history[] | "Version: \(.version)\nDate: \(.date)\nNotes: \(.notes)\n"' "$VERSION_FILE"
    else
        echo "jq command not found. Please install jq for better output."
        cat "$VERSION_FILE"
    fi
}

# Update the version using Laravel artisan command
function update_version {
    LARAVEL_ROOT=$(get_laravel_root)
    TYPE=${1:-"patch"}
    NOTES=""
    NO_GIT=""
    
    # Parse options
    for arg in "${@:2}"; do
        if [[ $arg == --notes=* ]]; then
            NOTES="${arg#*=}"
        elif [[ $arg == --no-git ]]; then
            NO_GIT="--no-git"
        fi
    done
    
    # Get current version
    CURRENT_VERSION=$(get_version)
    
    # Show confirmation
    echo "========================================================"
    echo "             VERSION UPDATE CONFIRMATION                "
    echo "========================================================"
    echo "Current version: $CURRENT_VERSION"
    echo "Update type: $TYPE"
    if [ -n "$NOTES" ]; then
        echo "Release notes: $NOTES"
    fi
    echo ""
    echo "This will create a new version and update config/app.php"
    if [ -z "$NO_GIT" ]; then
        echo "It will also create a Git commit and tag for this version"
    fi
    echo "========================================================"
    
    # Ask for confirmation
    read -p "Do you want to proceed with this update? (y/n): " confirm
    if [[ "$confirm" != "y" && "$confirm" != "Y" ]]; then
        echo "Version update canceled"
        return 1
    fi
    
    # Build command
    CMD="php artisan version:update $TYPE"
    
    if [ -n "$NOTES" ]; then
        CMD="$CMD --notes=\"$NOTES\""
    fi
    
    if [ -n "$NO_GIT" ]; then
        CMD="$CMD $NO_GIT"
    fi
    
    # Execute command
    cd "$LARAVEL_ROOT"
    echo "Updating version ($TYPE)..."
    eval "$CMD"
}

# Create a git tag for the current version
function create_tag {
    LARAVEL_ROOT=$(get_laravel_root)
    VERSION=$(get_version)
    NOTES=""
    
    # Parse options
    for arg in "$@"; do
        if [[ $arg == --notes=* ]]; then
            NOTES="${arg#*=}"
        fi
    done
    
    if [ -z "$NOTES" ]; then
        read -p "Enter tag message: " NOTES
    fi
    
    cd "$LARAVEL_ROOT"
    
    echo "Creating git tag for version $VERSION..."
    git tag -a "v$VERSION" -m "$NOTES"
    
    if [ $? -eq 0 ]; then
        echo "Tag v$VERSION created successfully."
    else
        echo "Failed to create tag."
        exit 1
    fi
}

# Push changes to the repository
function push_to_repo {
    LARAVEL_ROOT=$(get_laravel_root)
    cd "$LARAVEL_ROOT"
    
    echo "Pushing changes to the repository..."
    git push origin $(git rev-parse --abbrev-ref HEAD) --tags
    
    if [ $? -eq 0 ]; then
        echo "Changes pushed successfully."
    else
        echo "Failed to push changes."
        exit 1
    fi
}

# Create a complete release
function create_release {
    TYPE=${1:-"patch"}
    NOTES=""
    NO_GIT=""
    
    # Parse options
    for arg in "${@:2}"; do
        if [[ $arg == --notes=* ]]; then
            NOTES="${arg#*=}"
        elif [[ $arg == --no-git ]]; then
            NO_GIT="--no-git"
        fi
    done
    
    # If no notes provided, prompt for them
    if [ -z "$NOTES" ]; then
        read -p "Enter release notes: " NOTES
    fi
    
    # Update version
    if [ -z "$NO_GIT" ]; then
        update_version "$TYPE" "--notes=$NOTES"
        
        # No need to create tag, as it was already done by the artisan command
        
        # Push changes
        push_to_repo
    else
        update_version "$TYPE" "--notes=$NOTES" "--no-git"
    fi
}

# Main execution logic
COMMAND=${1:-"help"}
shift

case $COMMAND in
    show)
        show_version
        ;;
    update)
        update_version "$@"
        ;;
    history)
        show_history
        ;;
    tag)
        create_tag "$@"
        ;;
    push)
        push_to_repo
        ;;
    release)
        create_release "$@"
        ;;
    *)
        show_help
        ;;
esac 