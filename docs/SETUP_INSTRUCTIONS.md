# Task Project Setup Instructions

Follow these steps in order to set up the Task project with our version control system.

## 1. Install Laravel

```bash
# Navigate to your project directory if not already there
# cd /Applications/MAMP/htdocs/task

# Install Laravel in the current directory
composer create-project laravel/laravel .

# Install required dependencies
composer require symfony/process
```

## 2. Create Directory Structure

```bash
# Create directories for version control system
mkdir -p scripts docs/roadmap backups/snapshots storage/app/reports
chmod -R 755 scripts
```

## 3. Create Initial Configuration Files

Laravel and the version control system are now set up. The next steps involve:

1. Making the scripts executable:
```bash
chmod +x scripts/*.sh
```

2. Setting up Git hooks:
```bash
cp scripts/pre-commit .git/hooks/
chmod +x .git/hooks/pre-commit
```

3. Initialize the version control system:
```bash
# Create initial version
php artisan vendor:publish --tag=task-version-control
php artisan task:init
```

4. Register the GitMonitor command in Laravel's scheduler:
Add this to `app/Console/Kernel.php` in the `schedule` method:
```php
$schedule->command('git:monitor')->hourly();
```

## 4. Verify Installation

```bash
# Verify Laravel installation
php artisan --version

# Verify version control system
php artisan task:status
``` 