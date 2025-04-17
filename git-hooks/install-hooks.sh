#!/bin/bash

# Get the absolute path to the project root
PROJECT_ROOT=$(git rev-parse --show-toplevel)

# Change to the project root directory
cd "$PROJECT_ROOT" || exit 1

# Create the hooks directory if it doesn't exist
mkdir -p .git/hooks

# Make the post-commit hook executable and copy it
chmod +x git-hooks/post-commit
cp git-hooks/post-commit .git/hooks/

echo "Git hooks installed successfully!" 