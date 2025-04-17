#!/bin/bash
#
# Script to install MailZila git hooks
#

# Get the current directory
CURRENT_DIR=$(dirname "$0")
PROJECT_ROOT=$(git rev-parse --show-toplevel)

# Output color settings
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Function to echo with colors
echo_color() {
  echo -e "${2}${1}${NC}"
}

echo_color "Installing MailZila git hooks..." "$BLUE"

# Check if .git/hooks directory exists
if [ ! -d "$PROJECT_ROOT/.git/hooks" ]; then
  echo_color "Error: .git/hooks directory not found. Are you in a git repository?" "$RED"
  exit 1
fi

# Install post-commit hook
echo_color "Installing post-commit hook..." "$YELLOW"
cp "$CURRENT_DIR/hooks/post-commit" "$PROJECT_ROOT/.git/hooks/post-commit"
chmod +x "$PROJECT_ROOT/.git/hooks/post-commit"

# Check if installation was successful
if [ $? -eq 0 ]; then
  echo_color "Successfully installed post-commit hook." "$GREEN"
else
  echo_color "Failed to install post-commit hook." "$RED"
  exit 1
fi

echo_color "Git hooks installation completed." "$GREEN"
echo_color "Hooks installed:" "$BLUE"
echo_color "  - post-commit: Generates AI tasks based on recent commits" "$BLUE"

exit 0 