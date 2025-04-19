#!/bin/bash
# scripts/setup-auto-tasks.sh
# Purpose: Set up the auto task creation system

set -e

echo "📋 Setting up auto task creation system..."

# Ensure project-management directory exists
if [ ! -d "project-management" ]; then
  echo "❌ Error: project-management directory not found"
  exit 1
fi

# Ensure auto-task-creator.sh exists and is executable
if [ ! -f "project-management/auto-task-creator.sh" ]; then
  echo "❌ Error: auto-task-creator.sh not found"
  exit 1
fi

chmod +x project-management/auto-task-creator.sh
echo "✅ Made auto-task-creator.sh executable"

# Create git hooks directory if it doesn't exist
mkdir -p .git/hooks

# Create post-commit hook
cat > .git/hooks/post-commit << 'EOF'
#!/bin/bash
# Git post-commit hook to automatically create tasks from commits

# Get the project root directory
project_dir="$(git rev-parse --show-toplevel)"

# Get the latest commit message
commit_message="$(git log -1 --pretty=%B)"

# Run the auto-task-creator script
"$project_dir/project-management/auto-task-creator.sh" --commit-message "$commit_message"

# Exit with the same status as the auto-task-creator script
exit $?
EOF

# Make post-commit hook executable
chmod +x .git/hooks/post-commit
echo "✅ Created and activated Git post-commit hook"

# Create an auto-task for the setup
./project-management/auto-task-creator.sh --title "Set up automatic task creation system" --type implement --commit-message "[automation] Set up auto task creation with Git hooks"

echo "✅ Auto task creation system is set up!"
echo "📝 Tasks will now be automatically created after each commit"
echo ""
echo "You can also create tasks manually with:"
echo "  ./project-management/auto-task-creator.sh --title \"Task title\" [--type fix|feature|implement]"
echo ""
echo "To test the system, try making a commit and check if a new task is created." 