#!/bin/bash

# MailZila Version Systems Integration
# This script integrates the GitHub version system with the existing version manager

set -e

# Define paths
EXISTING_VERSION_MANAGER="./project-management/version-manager.sh"
GITHUB_VERSION_MANAGER="./scripts/version-github-sync.sh"

# Check if both scripts exist
if [ ! -f "$EXISTING_VERSION_MANAGER" ]; then
    echo "Error: Existing version manager not found at $EXISTING_VERSION_MANAGER"
    exit 1
fi

if [ ! -f "$GITHUB_VERSION_MANAGER" ]; then
    echo "Error: GitHub version manager not found at $GITHUB_VERSION_MANAGER"
    exit 1
fi

# Make sure both scripts are executable
chmod +x "$EXISTING_VERSION_MANAGER"
chmod +x "$GITHUB_VERSION_MANAGER"

# Function to sync all 7 versions
function sync_all_versions() {
    echo "Synchronizing all versions with GitHub..."
    
    # Initialize all versions in GitHub
    "$GITHUB_VERSION_MANAGER" init
    
    # Create version branches
    "$GITHUB_VERSION_MANAGER" branches
    
    echo "All versions successfully synchronized with GitHub!"
}

# Function to handle version update
function handle_version_update() {
    local type=$1
    local notes=$2
    local skip_git=$3
    
    # First, update version using existing system
    if [ "$skip_git" == "true" ]; then
        "$EXISTING_VERSION_MANAGER" update "$type" --notes="$notes" --no-git
    else
        "$EXISTING_VERSION_MANAGER" update "$type" --notes="$notes"
    fi
    
    # Then sync with GitHub (if not skipping git)
    if [ "$skip_git" != "true" ]; then
        # Get current version
        local version=$("$GITHUB_VERSION_MANAGER" current | cut -d' ' -f3)
        
        # Sync with GitHub
        "$GITHUB_VERSION_MANAGER" sync "$version"
        
        echo "Version $version updated and synchronized with GitHub"
    fi
}

# Function to install hooks
function install_hooks() {
    echo "Installing version management hooks..."
    
    # Create hooks directory if it doesn't exist
    mkdir -p .git/hooks
    
    # Create post-version-update hook
    cat > .git/hooks/post-version-update << 'EOF'
#!/bin/bash
# This hook runs after a version update

# Get the current version
version=$(./scripts/version-github-sync.sh current | cut -d' ' -f3)

# Sync with GitHub
./scripts/version-github-sync.sh sync "$version"

echo "Version $version synchronized with GitHub"
EOF
    
    # Make hook executable
    chmod +x .git/hooks/post-version-update
    
    echo "Hooks installed successfully"
}

# Function to create version update command wrapper
function create_wrapper() {
    echo "Creating version update wrapper..."
    
    # Create wrapper script
    cat > ./scripts/version-update.sh << 'EOF'
#!/bin/bash
# MailZila version update wrapper

# This wrapper integrates local version management with GitHub

set -e

# Parameters
TYPE=${1:-"patch"}
NOTES="${2:-"Version update"}"
NO_GIT=${3:-"false"}

# Update version locally
./project-management/version-manager.sh update "$TYPE" --notes="$NOTES" $([ "$NO_GIT" == "true" ] && echo "--no-git" || echo "")

# If git operations are not skipped, sync with GitHub
if [ "$NO_GIT" != "true" ]; then
    # Get current version
    VERSION=$(./scripts/version-github-sync.sh current | cut -d' ' -f3)
    
    # Sync with GitHub
    ./scripts/version-github-sync.sh sync "$VERSION"
    
    echo "Version $VERSION synchronized with GitHub"
    
    # Ask if user wants to create a GitHub release
    read -p "Do you want to create a GitHub release for v$VERSION? (y/n) " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        read -p "Please enter your GitHub token: " GITHUB_TOKEN
        ./scripts/version-github-sync.sh release "$VERSION" "$GITHUB_TOKEN"
    fi
fi
EOF
    
    # Make wrapper executable
    chmod +x ./scripts/version-update.sh
    
    echo "Wrapper created successfully. Use ./scripts/version-update.sh to update versions."
}

# Main script execution
case "$1" in
    "sync-all")
        sync_all_versions
        ;;
    "update")
        handle_version_update "$2" "$3" "$4"
        ;;
    "install-hooks")
        install_hooks
        ;;
    "create-wrapper")
        create_wrapper
        ;;
    "setup")
        # Full setup: create wrapper, install hooks, sync all versions
        create_wrapper
        install_hooks
        sync_all_versions
        ;;
    *)
        echo "MailZila Version Systems Integration"
        echo "Usage:"
        echo "  $0 sync-all                Synchronize all versions with GitHub"
        echo "  $0 update [type] [notes] [skip_git]  Update version and sync with GitHub"
        echo "  $0 install-hooks           Install version management hooks"
        echo "  $0 create-wrapper          Create version update wrapper"
        echo "  $0 setup                   Full setup: wrapper, hooks, and sync"
        ;;
esac 