#!/bin/bash
# scripts/push-previous-version.sh
# Purpose: Push the version before the latest to the git repository

set -e

# Configuration
VERSION_FILE="version.json"
REMOTE_NAME=${1:-"origin"}
BRANCH_NAME=${2:-""}

# Check if version file exists
if [ ! -f "$VERSION_FILE" ]; then
  echo "Error: version.json not found."
  exit 1
fi

# Check if jq is installed
if ! command -v jq &> /dev/null; then
  echo "Error: jq is required for version processing."
  echo "Please install jq to continue:"
  echo "  - On macOS: brew install jq"
  echo "  - On Debian/Ubuntu: apt-get install jq"
  echo "  - On CentOS/RHEL: yum install jq"
  exit 1
fi

# Check if git repository exists
if ! git rev-parse --is-inside-work-tree > /dev/null 2>&1; then
  echo "Error: Not inside a git repository."
  exit 1
fi

# Determine the current branch name if not provided
if [ -z "$BRANCH_NAME" ]; then
  BRANCH_NAME=$(git rev-parse --abbrev-ref HEAD 2>/dev/null)
  if [ -z "$BRANCH_NAME" ] || [ "$BRANCH_NAME" = "HEAD" ]; then
    echo "Unable to determine current branch. Please specify a branch name."
    exit 1
  fi
  echo "Using current branch: $BRANCH_NAME"
fi

# Function to extract and sort versions
get_sorted_versions() {
  # Get versions from Git tags
  git_versions=$(git tag --list "v*" | grep -E '^v[0-9]+\.[0-9]+\.[0-9]+$' | sed 's/^v//' | sort -V)
  
  # Get versions from version.json
  json_versions=$(jq -r '.history[].version' "$VERSION_FILE" | sort -V)
  
  # Combine, deduplicate, and sort
  printf "%s\n%s" "$git_versions" "$json_versions" | sort -V | uniq
}

# Function to get latest and previous versions
get_latest_and_previous_versions() {
  local versions=$(get_sorted_versions)
  local count=$(echo "$versions" | wc -l | xargs)
  
  if [ "$count" -lt 2 ]; then
    echo "Error: Need at least 2 versions to determine latest and previous."
    exit 1
  fi
  
  # Get the last two versions
  local latest=$(echo "$versions" | tail -n 1)
  local previous=$(echo "$versions" | tail -n 2 | head -n 1)
  
  echo "$latest $previous"
}

# Function to add git remote if needed
add_remote_if_needed() {
  local remote_name="$1"
  local repo_url="$2"
  
  # Check if remote exists
  if ! git remote | grep -q "^$remote_name$"; then
    echo "Remote '$remote_name' not found. Adding it now..."
    git remote add "$remote_name" "$repo_url"
    echo "Added remote '$remote_name' pointing to '$repo_url'"
  else
    echo "Remote '$remote_name' already exists."
  fi
}

# Function to check if remote URL is set
check_remote_url() {
  local remote_name="$1"
  local remote_url=$(git remote get-url "$remote_name" 2>/dev/null || echo "")
  
  if [ -z "$remote_url" ]; then
    echo "Error: Remote '$remote_name' has no URL set."
    read -p "Enter the repository URL for $remote_name: " repo_url
    
    if [ -z "$repo_url" ]; then
      echo "Error: No repository URL provided."
      exit 1
    fi
    
    git remote set-url "$remote_name" "$repo_url" 2>/dev/null || add_remote_if_needed "$remote_name" "$repo_url"
    echo "Set '$remote_name' to point to '$repo_url'"
  fi
}

# Main function
main() {
  # Check if remote exists
  if ! git remote | grep -q "^$REMOTE_NAME$"; then
    echo "Remote '$REMOTE_NAME' not found."
    read -p "Enter the repository URL for $REMOTE_NAME: " repo_url
    
    if [ -z "$repo_url" ]; then
      echo "Error: No repository URL provided."
      exit 1
    fi
    
    git remote add "$REMOTE_NAME" "$repo_url"
    echo "Added remote '$REMOTE_NAME' pointing to '$repo_url'"
  else
    # Check if remote URL is set
    check_remote_url "$REMOTE_NAME"
  fi
  
  # Get latest and previous versions
  versions=$(get_latest_and_previous_versions)
  latest_version=$(echo "$versions" | cut -d ' ' -f 1)
  previous_version=$(echo "$versions" | cut -d ' ' -f 2)
  
  echo "Latest version: v$latest_version"
  echo "Previous version: v$previous_version (this is the version that will be pushed)"
  
  # Confirm before proceeding
  read -p "Continue with pushing v$previous_version to $REMOTE_NAME/$BRANCH_NAME? (y/n): " confirm
  if [[ ! "$confirm" =~ ^[Yy]$ ]]; then
    echo "Operation cancelled."
    exit 0
  fi
  
  # Push the previous version tag
  echo "Pushing v$previous_version tag to $REMOTE_NAME..."
  if git push "$REMOTE_NAME" "v$previous_version"; then
    echo "✅ Successfully pushed tag v$previous_version to $REMOTE_NAME"
  else
    echo "❌ Failed to push tag v$previous_version"
    exit 1
  fi
  
  # Push branch to remote
  echo "Pushing $BRANCH_NAME branch to $REMOTE_NAME..."
  if git push "$REMOTE_NAME" "$BRANCH_NAME"; then
    echo "✅ Successfully pushed $BRANCH_NAME branch to $REMOTE_NAME"
  else
    echo "❌ Failed to push $BRANCH_NAME branch"
    exit 1
  fi
  
  echo "✅ Completed pushing v$previous_version to $REMOTE_NAME/$BRANCH_NAME"
}

# Run the main function
main "$@" 