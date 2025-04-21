# Task Development Workflow

This document outlines the complete development workflow for the Task project, including the integration of AI Agent capabilities at each step.

## Workflow Steps

### 1. Project Planning
- **User:** Defines project requirements and goals
- **AI Agent:** Analyzes requirements and suggests phase organization [AI-RM-001]
- **Commands:** None (planning phase)

### 2. Roadmap Phase Creation
- **User:** Runs `./scripts/roadmap-phase.sh "Phase Name" "Description"`
- **AI Agent:** Validates phase structure and generates documentation [AI-RM-001]
- **System:** Creates phase branch, JSON tracking, and markdown documentation
- **Example:** `./scripts/roadmap-phase.sh "User Authentication" "Implement secure login system"`

### 3. Feature Creation
- **User:** Runs `./scripts/new-feature.sh feature-name phase-name`
- **AI Agent:** Tracks feature in database, suggests related features [AI-FT-003]
- **System:** Creates feature branch with naming convention: `feature/YYYYMMDD_PHASE_feature-name`
- **Example:** `./scripts/new-feature.sh login-system P2-CORE`

### 4. Code Development
- **User:** Develops code on feature branch
- **AI Agent:** Provides code quality suggestions [AI-CD-011] and proactive monitoring [AI-PM-004]
- **System:** Automatically runs background checks on code quality
- **Tools:** IDE integration, Code analyzer scripts

### 5. Pre-Commit Validation
- **User:** Commits code changes
- **AI Agent:** Validates commit message references [AI-GT-007] and checks code security [AI-SC-009]
- **System:** Enforces tests, migration checks, and reference conventions through pre-commit hooks
- **Example:** `git commit -m "Add login controller [REF:P2-CORE]"`

### 6. Version Release
- **User:** Runs `./scripts/release.sh [major|minor|patch] "Release notes"`
- **AI Agent:** Advises on semantic versioning decisions [AI-VC-002]
- **System:** Updates version.json, creates Git tag, updates app version, creates backup
- **Example:** `./scripts/release.sh minor "Added user authentication system"`

### 7. Automated Backups
- **User:** Backups created automatically during releases
- **AI Agent:** Verifies backup integrity and suggests restoration tests [AI-BR-004]
- **System:** Organizes backups by version with full metadata
- **Location:** `/backups/snapshots/v{VERSION}/`

### 8. Project Monitoring
- **User:** Runs `php artisan git:monitor` 
- **AI Agent:** Generates customized reports with insights [AI-DX-010]
- **System:** Captures git status, branch info, and project metrics
- **Reports:** Stored in `storage/app/private/reports/`

### 9. Regular Check-ins
- **User:** Reviews AI feedback during weekly check-ins
- **AI Agent:** Identifies potential roadblocks and suggests optimizations [AI-RC-006]
- **System:** Scheduled automated analysis runs each Monday
- **Frequency:** Weekly (configurable in `ai-assistant.json`)


## AI Agent Responsibilities

The Task AI Agent provides continuous support through the following responsibilities:

| ID | Type | Description | Frequency |
|----|------|-------------|-----------|
| AI-RM-001 | Roadmap Monitoring | Track and report on roadmap phases and progress | Daily |
| AI-VC-002 | Version Control | Monitor version control adherence and assist with versioning tasks | Continuous |
| AI-FT-003 | Feature Tracking | Track feature implementation and assist with branch management | Daily |
| AI-BR-004 | Backup & Restore | Assist with backup and restoration procedures | Weekly |
| AI-CM-005 | Change Management | Support change management process and ensure proper documentation | Continuous |
| AI-RC-006 | Regular Check-ins | Provide regular status updates and identify potential roadblocks | Weekly (Monday) |
| AI-GT-007 | Git Workflow | Optimize git branch strategies and enforce commit message standards | Daily |

## Example Complete Workflow Scenario

1. Product manager defines a new feature requirement: "Add user authentication"
2. Team creates a new roadmap phase: `./scripts/roadmap-phase.sh "Core Features" "Essential functionality for MVP"`
3. Developer creates feature branch: `./scripts/new-feature.sh user-authentication P2-CORE`
4. Developer implements code on feature branch with AI code quality assistance
5. Pre-commit hook validates references and code quality
6. After feature completion, team releases minor version: `./scripts/release.sh minor "Added user authentication"`
7. System creates automatic backups and updates version tracking
8. Team runs monitoring: `php artisan git:monitor`
9. Weekly AI check-in provides insights on the new feature implementation
