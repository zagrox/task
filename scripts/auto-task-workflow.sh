#!/bin/bash

# Set up the git hooks directory if it doesn't exist
mkdir -p .git/hooks

# Copy the post-commit hook
cp scripts/post-commit .git/hooks/
chmod +x .git/hooks/post-commit

# Generate AI tasks based on recent changes
php artisan tasks:generate-ai --analyze-git

echo "🤖 Auto-task system is set up!"
echo "✅ Tasks will be auto-generated based on code changes"
echo "✅ Include 'fixes task #ID' or 'closes task #ID' in commit messages to auto-complete tasks"
echo
echo "Try running 'php artisan tasks:generate-ai --analyze-git' to generate tasks from recent changes." 