# Task Restoration Guide

This document provides comprehensive instructions for restoring Task to a specific version or point in time.

## Quick Reference

| Restoration Type | Command |
|------------------|---------|
| Code only | `git checkout v1.0.0` |
| Database only | `mysql -u username -p task < backups/snapshots/v1.0.0/task_v1.0.0_20230101_120000.sql` |
| Full restore | See "Complete Project Restoration" section |

## 1. Code Restoration

### Restore to a specific version tag:

```bash
# List available versions
git tag

# Checkout specific version
git checkout v1.0.0
```

### Restore to a specific commit:

```bash
# View commit history
git log --oneline

# Checkout specific commit
git checkout abc1234
```

### Create a new branch from a restoration point:

```bash
git checkout -b restored-branch v1.0.0
```

## 2. Database Restoration

### Find the appropriate backup:

```bash
# List all backups
find backups/snapshots -name "*.sql" -o -name "*.sql.gz"
```

### Restore MySQL database:

```bash
# For plain SQL files
mysql -u username -p task < backups/snapshots/v1.0.0/task_v1.0.0_20230101_120000.sql

# For gzipped SQL files
zcat backups/snapshots/v1.0.0/task_v1.0.0_20230101_120000.sql.gz | mysql -u username -p task
```

### Using Laravel's migration system:

```bash
# Reset and rerun all migrations
php artisan migrate:fresh

# Then import data from backup
mysql -u username -p task < backups/snapshots/v1.0.0/task_v1.0.0_20230101_120000.sql
```

## 3. Environment Restoration

### Restore .env file:

```bash
# Copy the backed up .env file
cp backups/snapshots/v1.0.0/.env.backup .env
```

## 4. Storage Files Restoration

### Restore storage files:

```bash
# For full backups
tar -xzf backups/snapshots/v1.0.0/storage_20230101_120000.tar.gz -C ./

# For incremental backups, restore the base first, then incrementals in chronological order
tar -xzf backups/snapshots/v1.0.0/storage_20230101_120000.tar.gz -C ./
tar -xzf backups/snapshots/v1.0.0/storage_20230101_120000_incr.tar.gz -C ./
```

## 5. Complete Project Restoration

Follow these steps to fully restore the project to a specific version:

1. **Checkout the code version:**
   ```bash
   git checkout v1.0.0
   ```

2. **Restore environment:**
   ```bash
   cp backups/snapshots/v1.0.0/.env.backup .env
   ```

3. **Install dependencies:**
   ```bash
   composer install
   npm install
   ```

4. **Restore database:**
   ```bash
   # Option 1: Fresh migrations then import data
   php artisan migrate:fresh
   mysql -u username -p task < backups/snapshots/v1.0.0/task_v1.0.0_20230101_120000.sql
   
   # Option 2: Direct import (no migrations)
   mysql -u username -p task < backups/snapshots/v1.0.0/task_v1.0.0_20230101_120000.sql
   ```

5. **Restore storage files:**
   ```bash
   tar -xzf backups/snapshots/v1.0.0/storage_20230101_120000.tar.gz -C ./
   ```

6. **Clear caches:**
   ```bash
   php artisan optimize:clear
   ```

7. **Verify the restoration:**
   ```bash
   # Check Laravel version
   php artisan --version
   
   # Check application status
   php artisan task:status
   
   # Run tests
   php artisan test
   ```

## 6. Emergency Restoration

If the system is completely down, follow these steps:

1. Clone the repository to a new location:
   ```bash
   git clone https://github.com/zagrox/task.git new-task
   cd new-task
   ```

2. Checkout the last known working version:
   ```bash
   git checkout v1.0.0
   ```

3. Follow steps 2-7 from the "Complete Project Restoration" section above.

## 7. Troubleshooting

### Database restoration issues:

- **Error: Table already exists**
  - Solution: Drop the database and create a new one before importing
  ```bash
  mysql -u username -p -e "DROP DATABASE task; CREATE DATABASE task;"
  ```

### File permission issues:

- **Error: Permission denied on storage files**
  - Solution: Reset permissions
  ```bash
  chmod -R 755 storage bootstrap/cache
  ```

### Dependency issues:

- **Error: Composer packages incompatible**
  - Solution: Try using the composer.lock from the backup
  ```bash
  cp backups/snapshots/v1.0.0/composer.lock ./composer.lock
  composer install
  ```

## 8. Getting Help

If you continue to experience restoration issues, contact:

- Development Team: dev@example.com
- System Administrator: admin@example.com
- AI Assistant: Use the help command with `php artisan task:assistant restore` 