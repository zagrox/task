# MailZila Project Management Tools

This directory contains tools for project management in the MailZila application.

## Task Management System

MailZila includes two complementary task management tools:

1. A shell script (`task-manager.sh`) for CLI operations
2. A Laravel Artisan command (`php artisan task:manage`) for integrated Laravel operations

Both tools share the same data file (`tasks.json`) and provide similar functionality with automatic backups.

## Shell Script Usage: `task-manager.sh`

```bash
./task-manager.sh command [options]
```

### Available Commands:

- `add`: Add a new task
- `list`: List tasks
- `show`: Show task details
- `update`: Update a task
- `note`: Add a note to a task
- `delete`: Delete a task
- `report`: Generate reports
- `export`: Export tasks to different formats
- `help`: Display help information

### Examples:

```bash
# Add a new task
./task-manager.sh add --title "Implement login page" --description "Create responsive login page with validation" --assignee user --priority high

# List all tasks
./task-manager.sh list

# List tasks with specific status
./task-manager.sh list --status "in-progress"

# Show specific task
./task-manager.sh show --id 1

# Update task status
./task-manager.sh update --id 1 --field status --value completed

# Add a note to a task
./task-manager.sh note --id 1 --content "Added form validation"

# Delete a task
./task-manager.sh delete --id 1

# Generate a summary report
./task-manager.sh report --type summary

# Export tasks to CSV
./task-manager.sh export --format csv --output tasks.csv
```

## Laravel Artisan Command: `php artisan task:manage`

```bash
php artisan task:manage action [options]
```

### Available Actions:

- `add`: Add a new task
- `list`: List tasks
- `show`: Show task details
- `update`: Update a task
- `note`: Add a note to a task
- `delete`: Delete a task
- `report`: Generate reports
- `export`: Export tasks to different formats

### Examples:

```bash
# Add a new task
php artisan task:manage add --title="Implement login page" --description="Create responsive login page with validation" --assignee=user --priority=high

# List all tasks
php artisan task:manage list

# List tasks with specific status
php artisan task:manage list --status=in-progress

# Show specific task
php artisan task:manage show --id=1

# Update task status
php artisan task:manage update --id=1 --field=status --value=completed

# Add a note to a task
php artisan task:manage note --id=1 --note="Added form validation"

# Delete a task
php artisan task:manage delete --id=1

# Generate a summary report
php artisan task:manage report --report-type=summary

# Export tasks to CSV
php artisan task:manage export --format=csv --output=/path/to/tasks.csv
```

## Task Data Structure

Both tools use the same JSON data structure:

```json
{
  "metadata": {
    "total_tasks": 2,
    "completed_tasks": 1,
    "user_tasks": 1,
    "ai_tasks": 1,
    "last_updated": "2023-06-10T14:30:00Z"
  },
  "next_id": 3,
  "tasks": [
    {
      "id": 1,
      "title": "Implement login page",
      "description": "Create responsive login page with validation",
      "assignee": "user",
      "status": "completed",
      "priority": "high",
      "created_at": "2023-06-01T10:00:00Z",
      "updated_at": "2023-06-10T14:30:00Z",
      "due_date": "2023-06-15",
      "related_feature": "Authentication",
      "related_phase": "Frontend",
      "dependencies": [2],
      "progress": 100,
      "notes": [
        {
          "content": "Added form validation",
          "timestamp": "2023-06-05T11:20:00Z"
        }
      ],
      "tags": ["frontend", "auth"],
      "estimated_hours": 8,
      "actual_hours": 10
    }
  ]
}
```

## Backups

Both tools automatically create backups before making changes to the tasks file. Backups are stored in the `project-management/backups` directory with timestamps.

## Scheduled Tasks

The Laravel command is configured to run a daily report at midnight, with output saved to `storage/logs/task-reports.log`.

## Integration

- The shell script is ideal for quick operations from the terminal
- The Laravel command integrates with the application's ecosystem and can be used in scheduled tasks

Both tools maintain the same data, so you can use either one based on your current context.

## Auto Task Creator

The `auto-task-creator.sh` script automatically creates tasks based on git commits and can include agent result summaries as notes.

### Requirements

- `jq` (JSON processor)
- `git`

### Usage

```bash
./auto-task-creator.sh [options]
```

#### Options

- `--commit-message "Message"`: Use a specific commit message instead of the latest one
- `--agent-summary "Summary text"`: Add agent results as a note to the created task
- `--title "Task title"`: Specify a custom task title (otherwise generated from commit)
- `--type fix|feature|implement`: Specify task type (otherwise inferred from commit message)

#### Examples

1. Create a task from the latest commit:
   ```bash
   ./auto-task-creator.sh
   ```

2. Create a task with a specific commit message and agent summary:
   ```bash
   ./auto-task-creator.sh --commit-message "Fix pagination in task list" --agent-summary "Fixed the pagination by adding proper page controls and ensuring current page state is maintained."
   ```

3. Create a task with a custom title and type:
   ```bash
   ./auto-task-creator.sh --title "Implement task sorting feature" --type feature
   ```

### Integration with Git Hooks

You can automatically create tasks after each commit by adding this script to your post-commit hook.

1. Create or edit `.git/hooks/post-commit`:
   ```bash
   #!/bin/bash
   project_dir="$(git rev-parse --show-toplevel)"
   "$project_dir/project-management/auto-task-creator.sh"
   ```

2. Make the hook executable:
   ```bash
   chmod +x .git/hooks/post-commit
   ```

## Task Workflow

The typical workflow for using this automation:

1. Make changes to your code
2. Commit changes with a descriptive message following conventions:
   - `fix: description` for bug fixes
   - `feat: description` for new features
   - `implement: description` for implementations
   - Use `[urgent]`, `[critical]`, or `[important]` tags for priority
   - Use `[ai]` tag for AI-assisted work
3. The task will be automatically created with appropriate metadata
4. Agent result summaries can be added manually using the `--agent-summary` option 