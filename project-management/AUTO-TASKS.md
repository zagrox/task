# Automatic Task Creation System

This document explains the automatic task creation system that generates tasks from Git commits and other project activities.

## Overview

The auto task creation system automatically creates tasks in the task management system based on:
- Git commits (via post-commit hook)
- Releases (via the release script)
- Manual entries for specific work items

This ensures all work is properly tracked and visible in the task management dashboard.

## Setup

The auto task creation system is already set up with:
1. A post-commit Git hook that runs after each commit
2. An executable auto-task-creator.sh script
3. Integration with the release process

If you need to reinstall or reconfigure the system, run:
```bash
./scripts/setup-auto-tasks.sh
```

## Manual Task Creation

You can manually create tasks using the auto-task-creator.sh script:

```bash
# Basic usage with defaults
./project-management/auto-task-creator.sh

# Creating a task with a specific title and type
./project-management/auto-task-creator.sh --title "Implement feature X" --type feature

# Creating a task with a commit message and agent summary
./project-management/auto-task-creator.sh \
  --commit-message "Fixed bug in login system" \
  --agent-summary "Fixed the authentication issue by updating the JWT token handling"
```

### Available Options

- `--title`: Task title (default: derived from commit message)
- `--type`: Task type - feature, fix, implement, document, etc. (default: derived from commit message)
- `--commit-message`: The commit message to parse (default: latest commit)
- `--agent-summary`: Additional notes to be added to the task

## Task Types and Priorities

The system detects task types and priorities based on commit message patterns:

- **Types**:
  - `fix:` or `bugfix:` → Fix type
  - `feat:` or `feature:` → Feature type
  - `implement:` or `implementation:` → Implementation type
  - `doc:` or `docs:` → Documentation type

- **Priorities**:
  - `[urgent]` or `[critical]` → High priority
  - `[important]` → Medium priority
  - Default → Low priority

## Assignees

The system automatically assigns tasks based on the commit author:
- If the commit message contains `[ai]` or the author contains "AI", the task is assigned to "ai"
- Otherwise, it's assigned to "user"

## Version Information

Tasks are automatically assigned the current version from the `version.json` file when created.

## Viewing Tasks

All tasks created by the system can be viewed:
- In the web interface at `/tasks`
- By directly examining the `project-management/tasks.json` file

## Troubleshooting

If tasks are not being created automatically:

1. Check that the post-commit hook is installed and executable:
   ```bash
   ls -la .git/hooks/post-commit
   ```

2. Verify the auto-task-creator.sh script is executable:
   ```bash
   ls -la project-management/auto-task-creator.sh
   ```

3. Run the setup script again:
   ```bash
   ./scripts/setup-auto-tasks.sh
   ```

4. Test with a manual task creation:
   ```bash
   ./project-management/auto-task-creator.sh --title "Test task" --type test
   ``` 