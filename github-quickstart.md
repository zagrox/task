# MailZila GitHub Integration: Quick Start Guide

This guide will help you quickly set up and start using the GitHub integration with your MailZila Task Manager.

## Setup (One-time)

1. Edit your `.env` file and add these lines:
   ```
   GITHUB_REPOSITORY="yourusername/mailzila"
   GITHUB_ACCESS_TOKEN="your_personal_access_token"
   ```

2. Create a Personal Access Token on GitHub:
   - Go to GitHub → Settings → Developer settings → Personal access tokens → Tokens (classic)
   - Generate a new token with `repo` permissions
   - Copy the token to your `.env` file

3. Run the database migration:
   ```bash
   php artisan migrate
   ```

## Syncing Tasks

### Manual Sync via Web Interface

1. Open any task detail page
2. Click the "Sync to GitHub" button
3. The task will be created/updated as a GitHub issue

### Sync via Command Line

```bash
# Sync all tasks
php artisan tasks:sync-to-github --all

# Sync tasks with "pending" status
php artisan tasks:sync-to-github --status=pending

# Sync a specific task
php artisan tasks:sync-to-github --task-id=123
```

## Automatic Sync

Tasks are automatically synced to GitHub daily at midnight. This schedule can be modified in `app/Console/Kernel.php`.

## Viewing GitHub Issues

After syncing a task, you'll see a GitHub issue number next to the "Sync to GitHub" button on the task detail page. Click this number to view the issue on GitHub.

## Keeping Tasks & Issues in Sync

When a GitHub issue is updated, those changes can be pulled back to your task manager using webhooks:

1. Go to your GitHub repository → Settings → Webhooks
2. Add webhook:
   - Payload URL: `https://your-app-url.com/api/github/webhook`
   - Content type: `application/json`
   - Events: Select "Issues" and "Issue comments"

## Troubleshooting

If you encounter issues:

1. Check your `.env` file has the correct GitHub repository and token
2. Ensure your GitHub token has `repo` permissions
3. Check the logs at `storage/logs/github-sync.log`

For more detailed information, see the [GitHub Integration documentation](github-integration.md). 