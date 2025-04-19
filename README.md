# MailZila

A mail management application with comprehensive version control and project management.

## Description

MailZila is a project focused on providing mail management services with enterprise-grade version control, automated backups, and structured development processes.

## Project Structure

- `/docs` - Project documentation
  - `ROADMAP.md` - Project roadmap and phase information
  - `RESTORE.md` - Restoration procedures and guides
  - `SETUP_INSTRUCTIONS.md` - Initial setup instructions
  - `PROJECT-MANAGEMENT.md` - Task management system documentation
  - `/roadmap` - Detailed phase documentation
- `/scripts` - Shell scripts for version control, backup, and automation
  - `task-manager.sh` - Task management script for users and AI
- `/project-management` - Task tracking data
  - `tasks.json` - Central task repository
- `/backups` - Backup storage location
- `/app` - Laravel application code
  - `/Console/Commands/GitMonitor.php` - Git monitoring command
  - `/Console/Commands/ProjectDashboard.php` - Project dashboard command

## Version Control System

MailZila incorporates a robust version control framework:

- **Semantic Versioning**: Automated versioning with comprehensive history
- **Feature Tracking**: Branch automation tied to roadmap phases
- **Backup System**: Incremental and full backup capabilities for code, database, and assets
- **Restoration Procedures**: Comprehensive guides for system restoration
- **Roadmap Management**: Structured project phases with milestone tracking

## Project Management System

The project includes an integrated task management system:

- **Task Tracking**: Central JSON-based task repository for both users and AI
- **Command-line Management**: Shell script for adding, updating, and listing tasks
- **Project Dashboard**: Interactive artisan command with filtering and sorting
- **AI Integration**: Transparent tracking of AI agent tasks alongside user tasks

## Getting Started

### Prerequisites
- PHP 7.4+ with Laravel framework
- MySQL or compatible database
- Git
- jq utility for JSON processing (`brew install jq`)

### Installation
Follow the setup instructions in `docs/SETUP_INSTRUCTIONS.md` to initialize the project.

### Development Workflow
1. Start a new feature with `./scripts/new-feature.sh feature-name phase-id`
2. Create a new phase with `./scripts/roadmap-phase.sh phase-name "Description"`
3. Make a release with `./scripts/release.sh [major|minor|patch] "Release notes"`
4. Create backups with `./scripts/backup.sh`
5. Monitor project status with `php artisan git:monitor`
6. Manage tasks with `./scripts/task-manager.sh [add|update|note|list|report]`
7. View project dashboard with `php artisan project:dashboard`

## Roadmap

The project roadmap is available in `docs/ROADMAP.md` with detailed phase information in the `docs/roadmap/` directory.

## Task Management

For task management details, refer to `docs/PROJECT-MANAGEMENT.md` to understand the task system structure and usage.

## Restoration

In case of system failure, refer to `docs/RESTORE.md` for complete restoration procedures.

## Git Version Management Scripts

The project includes several scripts for managing Git version pushing:

### `scripts/push-previous-version.sh`

Pushes the version before the latest one to a Git repository. This is useful for ensuring the previous stable version is always available in the remote repository.

```bash
./scripts/push-previous-version.sh [remote] [branch]
```

- `remote`: (Optional) The remote repository name (default: "origin")
- `branch`: (Optional) The branch name (default: current branch)

### `scripts/push-all-versions.sh`

Pushes all version tags to a Git repository in sequential order.

```bash
./scripts/push-all-versions.sh [remote] [branch]
```

- `remote`: (Optional) The remote repository name (default: "origin")
- `branch`: (Optional) The branch name (default: current branch)

### `scripts/push-version.sh`

Pushes a specific version tag to a Git repository.

```bash
./scripts/push-version.sh <version> [remote] [branch]
```

- `version`: (Required) The version to push (e.g., "1.0.7" or "v1.0.7")
- `remote`: (Optional) The remote repository name (default: "origin")
- `branch`: (Optional) The branch name (default: current branch)

All scripts will prompt for the repository URL if the remote is not configured, and will automatically detect and use your current branch if no branch is specified.
