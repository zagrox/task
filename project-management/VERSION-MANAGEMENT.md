# Version Management for MailZila

This document outlines how version management is implemented in the MailZila application and provides instructions for updating versions and managing releases.

## Version Structure

MailZila uses semantic versioning (MAJOR.MINOR.PATCH) according to [SemVer](https://semver.org/) standards:

1. **MAJOR** version for incompatible API changes
2. **MINOR** version for new functionality in a backward compatible manner
3. **PATCH** version for backward compatible bug fixes

## ⚠️ Important Safety Guidelines

1. **Never Downgrade Versions**: 
   - Downgrading from a higher version (e.g., 1.0.7 to 1.0.0) can cause serious issues
   - If absolutely necessary, use the `--force` option, but only with extreme caution

2. **Always Confirm Version Changes**:
   - All version updates will now require explicit confirmation
   - Major version upgrades will require double confirmation

3. **Backups Are Automatic**:
   - The version synchronization script now automatically backs up your version.json
   - Interrupted scripts will restore your original version

4. **Respect Current Production Version**:
   - Current production version is **1.0.7**
   - All tools will preserve this version unless explicitly changed

## Tools Available

The following tools are available for version management:

### 1. Laravel Artisan Command

```bash
php artisan version:update [type] [--notes="Release notes"] [--no-git] [--force]
```

Options:
- `type`: `major`, `minor`, or `patch` (default: `patch`)
- `--notes`: Release notes for this version
- `--no-git`: Skip git commit and tag operations
- `--force`: Force version change even if it would be a downgrade (use with caution!)

### 2. Shell Script

A utility script is available at `project-management/version-manager.sh` with the following commands:

```bash
./project-management/version-manager.sh [command] [options]
```

Commands:
- `show`: Display current version information
- `update [type]`: Update version number (patch, minor, major)
- `history`: Show version history
- `tag`: Create a git tag for the current version
- `push`: Push current version to repository
- `release [type]`: Create a complete release (update, tag, and push)
- `help`: Display help message

Options:
- `--notes="Release notes"`: Specify release notes for version updates
- `--no-git`: Skip git operations for version updates

### 3. Web Interface

The application includes a version management interface at `/tasks-versions` that shows:
- Current version information
- Git status (uncommitted changes, unpushed commits)
- Version history
- Option to push changes to the repository

## Automated Version Management

The application includes scheduled tasks for version management:

1. A monthly reminder runs on the first Monday of each month at 9:00 AM that reminds administrators to check for version updates.
2. Task records are automatically created for version updates and repository pushes.

## Version File Structure

The version information is stored in `version.json` at the root of the application with the following structure:

```json
{
  "major": 1,
  "minor": 0,
  "patch": 7,
  "history": [
    {
      "version": "1.0.7",
      "date": "2025-04-17",
      "notes": "Added version management tab"
    },
    ...
  ],
  "previous_versions": []
}
```

## Git Integration

Version updates are integrated with Git in the following ways:

1. When a version is updated (without `--no-git`):
   - Changes to version.json and config/app.php are committed
   - A new tag is created with the version number (e.g., v1.0.7)
   - The tag can be pushed to the repository

2. The version page shows:
   - Uncommitted changes
   - Unpushed commits
   - Current branch

## Git History Synchronization

For synchronizing version history with Git, use the `version-github-sync.sh` script:

```bash
./scripts/version-github-sync.sh
```

This script:
1. Reconstructs version history in Git from version.json
2. Creates appropriate tags with annotated messages
3. Simulates codebase changes for each version
4. Includes safeguards to prevent accidental version downgrades
5. Automatically backs up and restores the original version.json

**Note**: This is an advanced operation - use it only when necessary.

## Best Practices

1. **Version Increments**:
   - Increment PATCH for bug fixes and small changes
   - Increment MINOR for new features that don't break backward compatibility
   - Increment MAJOR for significant changes that break backward compatibility

2. **Release Notes**:
   - Write clear, concise release notes
   - Document important changes, new features, and bug fixes
   - Include any migration steps if necessary

3. **Git Workflow**:
   - Commit all changes before updating the version
   - Tag each version to mark it in the Git history
   - Push tags to the remote repository

## Task Integration

Version updates and repository pushes are tracked as tasks in the task management system:

1. Version updates create a task with:
   - Title: "Version update: [old] → [new]"
   - Description containing release notes
   - Status: "completed"
   - Related Feature: "Version Control"

2. Repository pushes create a task with:
   - Title: "Version [version] pushed to repository"
   - Status: "completed"
   - Related Feature: "Version Control"

The version controller will display all of this history in the versions page. 