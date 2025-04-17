# Version Management for MailZila

This document outlines how version management is implemented in the MailZila application and provides instructions for updating versions and managing releases.

## Version Structure

MailZila uses semantic versioning (MAJOR.MINOR.PATCH) according to [SemVer](https://semver.org/) standards:

1. **MAJOR** version for incompatible API changes
2. **MINOR** version for new functionality in a backward compatible manner
3. **PATCH** version for backward compatible bug fixes

## Tools Available

The following tools are available for version management:

### 1. Laravel Artisan Command

```bash
php artisan version:update [type] [--notes="Release notes"] [--no-git]
```

Options:
- `type`: `major`, `minor`, or `patch` (default: `patch`)
- `--notes`: Release notes for this version
- `--no-git`: Skip git commit and tag operations

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
  "patch": 0,
  "history": [
    {
      "version": "1.0.0",
      "date": "2023-07-01",
      "notes": "Initial release"
    }
  ],
  "previous_versions": []
}
```

## Git Integration

Version updates are integrated with Git in the following ways:

1. When a version is updated (without `--no-git`):
   - Changes to version.json and config/app.php are committed
   - A new tag is created with the version number (e.g., v1.0.0)
   - The tag can be pushed to the repository

2. The version page shows:
   - Uncommitted changes
   - Unpushed commits
   - Current branch

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