# GitHub Integration for MailZila Task Manager

This document explains how to set up GitHub integration for the MailZila task manager to synchronize tasks with GitHub issues.

## Configuration

1. Open your `.env` file and add the following configuration:

```
GITHUB_REPOSITORY="yourusername/yourrepo"
GITHUB_ACCESS_TOKEN="your_personal_access_token"
```

Replace:
- `yourusername/yourrepo` with your actual GitHub repository (e.g., `mailzila/task-manager`)
- `your_personal_access_token` with a GitHub personal access token

## Creating a GitHub Personal Access Token

1. Go to your GitHub account settings
2. Navigate to "Developer settings" > "Personal access tokens" > "Tokens (classic)"
3. Click "Generate new token"
4. Give your token a descriptive name (e.g., "MailZila Task Manager")
5. Select the following scopes:
   - `repo` (Full control of private repositories)
   - `workflow` (if you plan to use GitHub Actions)
6. Click "Generate token"
7. Copy the token and add it to your `.env` file

**Important**: Store this token securely. You won't be able to see it again once you navigate away from the page.

## Commands

### Sync Tasks to GitHub

```bash
# Sync all tasks
php artisan tasks:sync-to-github --all

# Sync a specific task
php artisan tasks:sync-to-github --task-id=123

# Sync tasks with a specific status
php artisan tasks:sync-to-github --status=pending

# Specify a different repository
php artisan tasks:sync-to-github --all --repository=otheruser/otherrepo
```

### Scheduled Synchronization

The task synchronization is scheduled to run daily at midnight. You can modify this schedule in `app/Console/Kernel.php`.

## Web Interface

You can also sync tasks from the web interface:

1. Navigate to a task detail page
2. Click the "Sync to GitHub" button

## GitHub Webhooks

To keep tasks updated when changes are made on GitHub:

1. Go to your GitHub repository settings
2. Navigate to "Webhooks" > "Add webhook"
3. Set the Payload URL to: `https://your-app-url.com/api/github/webhook`
4. Content type: `application/json`
5. Select "Let me select individual events"
6. Check "Issues" and "Issue comments"
7. Click "Add webhook"

This will enable two-way synchronization between your task manager and GitHub issues. 