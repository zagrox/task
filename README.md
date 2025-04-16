# MailZila

A mail management application with comprehensive version control and project management.

## Description

MailZila is a project focused on providing mail management services with enterprise-grade version control, automated backups, and structured development processes.

## Project Structure

- `/docs` - Project documentation
  - `ROADMAP.md` - Project roadmap and phase information
  - `RESTORE.md` - Restoration procedures and guides
  - `SETUP_INSTRUCTIONS.md` - Initial setup instructions
  - `/roadmap` - Detailed phase documentation
- `/scripts` - Shell scripts for version control, backup, and automation
- `/backups` - Backup storage location
- `/app` - Laravel application code
  - `/Console/Commands/GitMonitor.php` - Git monitoring command

## Version Control System

MailZila incorporates a robust version control framework:

- **Semantic Versioning**: Automated versioning with comprehensive history
- **Feature Tracking**: Branch automation tied to roadmap phases
- **Backup System**: Incremental and full backup capabilities for code, database, and assets
- **Restoration Procedures**: Comprehensive guides for system restoration
- **Roadmap Management**: Structured project phases with milestone tracking

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

## Roadmap

The project roadmap is available in `docs/ROADMAP.md` with detailed phase information in the `docs/roadmap/` directory.

## Restoration

In case of system failure, refer to `docs/RESTORE.md` for complete restoration procedures.
