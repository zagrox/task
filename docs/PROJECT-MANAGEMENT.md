# Project Management System

This document outlines the integrated task management system used in MailZila for tracking tasks, progress and coordinating work between team members and AI assistance.

## Overview

The task management system provides a structured approach to tracking work with:

- A central JSON-based task repository
- Command-line tools for task manipulation
- An interactive dashboard for monitoring project status
- Integration with the project phases and roadmap

## Task Structure

Each task in the system follows this structure:

```json
{
  "id": "T123",
  "title": "Implement feature X",
  "description": "Detailed description of the task",
  "status": "in-progress",
  "priority": "high",
  "created_by": "username",
  "assigned_to": "username",
  "created_at": "2023-06-15T10:30:00Z",
  "due_date": "2023-06-30T23:59:59Z",
  "estimated_hours": 5,
  "logged_hours": 3,
  "progress": 60,
  "feature": "auth",
  "phase": "alpha",
  "tags": ["frontend", "api"],
  "notes": [
    {
      "author": "username",
      "content": "Note content",
      "timestamp": "2023-06-20T14:22:00Z"
    }
  ],
  "history": [
    {
      "field": "status",
      "from": "pending",
      "to": "in-progress",
      "by": "username",
      "timestamp": "2023-06-16T09:15:00Z"
    }
  ]
}
```

### Task Fields

- **id**: Unique task identifier (Txxxx format)
- **title**: Short descriptive title
- **description**: Detailed task description
- **status**: Current status (pending, in-progress, review, blocked, completed)
- **priority**: Task priority (low, medium, high, critical)
- **created_by**: Username of task creator
- **assigned_to**: Username of assignee (or 'unassigned')
- **created_at**: ISO 8601 timestamp of creation
- **due_date**: ISO 8601 timestamp of deadline
- **estimated_hours**: Estimated time for completion
- **logged_hours**: Time logged on the task
- **progress**: Percentage of completion (0-100)
- **feature**: Associated feature identifier
- **phase**: Associated project phase identifier
- **tags**: Array of categorization tags
- **notes**: Array of comments/notes on the task
- **history**: Tracking of field changes

## Command-line Management

The `task-manager.sh` script provides command-line access to manage tasks:

### Adding Tasks

```bash
./scripts/task-manager.sh add \
  --title "Implement new API endpoint" \
  --desc "Create endpoint for user preferences" \
  --status "pending" \
  --priority "medium" \
  --assigned "john" \
  --estimated 4 \
  --due "2023-07-15" \
  --feature "user-prefs" \
  --phase "beta" \
  --tags "backend,api"
```

### Updating Tasks

```bash
./scripts/task-manager.sh update T123 \
  --status "in-progress" \
  --progress 30 \
  --logged 2
```

### Adding Notes

```bash
./scripts/task-manager.sh note T123 "Found issue with validation, need to revisit"
```

### Listing Tasks

```bash
./scripts/task-manager.sh list                    # List all tasks
./scripts/task-manager.sh list --status pending   # Filter by status
./scripts/task-manager.sh list --assigned john    # Filter by assignee
./scripts/task-manager.sh list --feature auth     # Filter by feature
./scripts/task-manager.sh list --sort priority    # Sort by priority
```

### Generating Reports

```bash
./scripts/task-manager.sh report progress       # Progress by feature
./scripts/task-manager.sh report workload       # Tasks by assignee
./scripts/task-manager.sh report upcoming       # Tasks due soon
```

## Project Dashboard

The Artisan command `php artisan project:dashboard` provides an interactive dashboard with additional filtering and visualization options:

```bash
# Basic dashboard
php artisan project:dashboard

# Filtered view
php artisan project:dashboard --user john --status in-progress,review

# Feature focus
php artisan project:dashboard --feature auth --sort priority

# Timeline view
php artisan project:dashboard --sort due --due-before "2023-08-01"
```

## Task States

Tasks progress through the following states:

1. **Pending**: Not yet started
2. **In-Progress**: Work has started but is not complete
3. **Review**: Work is complete but needs review
4. **Blocked**: Progress is blocked by an issue
5. **Completed**: Task is fully completed

## Priority Levels

Tasks can have the following priority levels:

- **Low**: Non-urgent, can be delayed
- **Medium**: Normal priority
- **High**: Important task requiring attention
- **Critical**: Urgent task requiring immediate attention

## Integration with Roadmap

Tasks are linked to the project roadmap through:

- The **phase** field, which connects to project phases
- The **feature** field, which groups tasks by feature sets

## Best Practices

1. Keep task titles concise but descriptive
2. Update task progress regularly
3. Add notes for context when status changes
4. Use tags consistently for filtering
5. Link related tasks with notes
6. Estimate task complexity realistically
7. Break down large tasks into smaller subtasks

## AI Integration

The task management system is designed to be equally usable by human team members and AI assistants:

- AI can create, update, and track tasks like any team member
- AI-assigned tasks are clearly marked with the assignee "ai-assistant"
- All tasks are visible to both human and AI team members for seamless collaboration 
``` 