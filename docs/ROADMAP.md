# MailZila Project Roadmap & Version Control System

This document outlines our comprehensive version control strategy and roadmap for the MailZila project.

## Project Version Control Architecture

### Step 1: Laravel-Optimized Version Control Strategy

#### Database Management
- Use Laravel migrations for all database schema changes
- Create the following script to enforce migrations with code changes:

```bash
#!/bin/bash
# scripts/ensure-migrations.sh
if [[ -n $(git diff --cached --name-only | grep "database/migrations") ]]; then
  echo "✅ Migrations detected in commit"
else
  echo "❌ Warning: No database migrations detected. Database changes should have migrations"
  read -p "Continue anyway? (y/n) " -n 1 -r
  echo
  if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    exit 1
  fi
fi
```

#### Feature Branch Automation
- Automated feature branch creation based on roadmap phases:

```bash
#!/bin/bash
# scripts/new-feature.sh
if [ $# -lt 1 ]; then
  echo "Usage: ./new-feature.sh feature-name [roadmap-phase]"
  exit 1
fi

FEATURE=$1
PHASE=${2:-"unspecified"}
DATE=$(date +"%Y%m%d")
BRANCH="feature/${DATE}_${PHASE}_${FEATURE}"

git checkout -b $BRANCH main
echo "Created branch $BRANCH"

# Record in features.json
if [ ! -f features.json ]; then
  echo '{"features":[]}' > features.json
fi

TMP=$(mktemp)
jq --arg feature "$FEATURE" \
   --arg phase "$PHASE" \
   --arg branch "$BRANCH" \
   --arg date "$(date +"%Y-%m-%d")" \
   '.features += [{"name":$feature,"phase":$phase,"branch":$branch,"created":$date,"status":"in-progress"}]' \
   features.json > "$TMP" && mv "$TMP" features.json

git add features.json
git commit -m "Start feature: $FEATURE (Phase: $PHASE)"

echo "Feature tracking initialized in features.json"
```

### Step 2: Version Management System

- Implement semantic versioning (major.minor.patch)
- Track version history with release notes
- Automatically create database backups with each version

```bash
#!/bin/bash
# scripts/release.sh
if [ $# -lt 1 ]; then
  echo "Usage: ./release.sh [major|minor|patch] \"Release notes\""
  exit 1
fi

TYPE=$1
NOTES=$2
VERSION_FILE="version.json"

# Create version file if it doesn't exist
if [ ! -f "$VERSION_FILE" ]; then
  echo '{"major":0,"minor":1,"patch":0,"history":[]}' > "$VERSION_FILE"
fi

# Increment version
TMP=$(mktemp)
if [ "$TYPE" == "major" ]; then
  jq '.major += 1 | .minor = 0 | .patch = 0' "$VERSION_FILE" > "$TMP"
elif [ "$TYPE" == "minor" ]; then
  jq '.minor += 1 | .patch = 0' "$VERSION_FILE" > "$TMP"
else
  jq '.patch += 1' "$VERSION_FILE" > "$TMP"
fi
mv "$TMP" "$VERSION_FILE"

# Get new version string
VERSION=$(jq -r '"\(.major).\(.minor).\(.patch)"' "$VERSION_FILE")

# Add to history
TMP=$(mktemp)
jq --arg version "$VERSION" \
   --arg date "$(date +"%Y-%m-%d")" \
   --arg notes "$NOTES" \
   '.history += [{"version":$version,"date":$date,"notes":$notes}]' \
   "$VERSION_FILE" > "$TMP" && mv "$TMP" "$VERSION_FILE"

# Commit and tag
git add "$VERSION_FILE"
git commit -m "Release v$VERSION"
git tag -a "v$VERSION" -m "$NOTES"

# Create database backup
BACKUP_DIR="backups/v$VERSION"
mkdir -p "$BACKUP_DIR"
php artisan db:dump --database="$BACKUP_DIR/mailzila_v${VERSION}.sql"

# Update Laravel version if needed
sed -i '' "s/'version' => '.*'/'version' => '$VERSION'/g" config/app.php

echo "Released v$VERSION"
echo "Don't forget to push: git push origin main --tags"
```

### Step 3: Roadmap Phase Tracking

- JSON-based roadmap tracking system
- Automatic branch creation for each phase
- Feature-to-phase association tracking

```bash
#!/bin/bash
# scripts/roadmap-phase.sh
if [ $# -lt 1 ]; then
  echo "Usage: ./roadmap-phase.sh phase-name \"Description\""
  exit 1
fi

PHASE=$1
DESC=$2
ROADMAP_FILE="roadmap.json"

# Create roadmap file if it doesn't exist
if [ ! -f "$ROADMAP_FILE" ]; then
  echo '{"phases":[], "current_phase": null}' > "$ROADMAP_FILE"
fi

# Add new phase
TMP=$(mktemp)
jq --arg phase "$PHASE" \
   --arg desc "$DESC" \
   --arg date "$(date +"%Y-%m-%d")" \
   '.phases += [{"name":$phase,"description":$desc,"start_date":$date,"features":[],"completed":false}] | .current_phase = $phase' \
   "$ROADMAP_FILE" > "$TMP" && mv "$TMP" "$ROADMAP_FILE"

# Create branch for the phase
git checkout -b "phase/$PHASE"
git add "$ROADMAP_FILE"
git commit -m "Start roadmap phase: $PHASE"

echo "Phase $PHASE initialized in $ROADMAP_FILE"
echo "Phase branch created: phase/$PHASE"
```

### Step 4: Complete Backup Solution

- Comprehensive backups (database, .env, and storage files)
- Timestamped snapshots with metadata
- Version-linked backup organization
- Integration with remote storage (optional)

```bash
#!/bin/bash
# scripts/backup.sh
VERSION=$(jq -r '"\(.major).\(.minor).\(.patch)"' version.json)
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
BACKUP_DIR="backups/snapshots/v$VERSION"

# Create directory
mkdir -p "$BACKUP_DIR"

# Backup database
php artisan db:dump --database="$BACKUP_DIR/mailzila_v${VERSION}_${TIMESTAMP}.sql"

# Backup .env
cp .env "$BACKUP_DIR/.env.backup"

# Backup storage files
tar -czf "$BACKUP_DIR/storage_${TIMESTAMP}.tar.gz" storage/app

# Create metadata
echo "Backup created: $TIMESTAMP" > "$BACKUP_DIR/metadata.txt"
echo "Version: $VERSION" >> "$BACKUP_DIR/metadata.txt"
echo "Git commit: $(git rev-parse HEAD)" >> "$BACKUP_DIR/metadata.txt"

# Add backup record to version.json
TMP=$(mktemp)
jq --arg time "$TIMESTAMP" \
   --arg path "$BACKUP_DIR" \
   '.backups += [{"timestamp":$time,"path":$path}]' \
   version.json > "$TMP" && mv "$TMP" version.json

git add version.json
git commit -m "Add backup metadata for $TIMESTAMP"

echo "Backup completed: $BACKUP_DIR"

# Optional - upload to external storage
if command -v rclone &> /dev/null; then
  rclone copy "$BACKUP_DIR" remote:mailzila-backups/v$VERSION
  echo "Uploaded to remote storage"
fi
```

### Step 5: Monitoring Dashboard

- Git activity monitoring through Laravel Artisan command
- Scheduled reports generation
- Integration with roadmap and feature tracking

```php
// App/Console/Commands/GitMonitor.php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class GitMonitor extends Command
{
    protected $signature = 'git:monitor';
    protected $description = 'Monitor git repository and generate reports';

    public function handle()
    {
        $output = [];
        
        // Current branch
        $process = Process::fromShellCommandline('git branch --show-current');
        $process->run();
        $output['current_branch'] = trim($process->getOutput());
        
        // Recent commits
        $process = Process::fromShellCommandline('git log --pretty=format:"%h|%an|%ad|%s" -n 10 --date=short');
        $process->run();
        $commits = [];
        foreach(explode("\n", $process->getOutput()) as $line) {
            [$hash, $author, $date, $message] = explode('|', $line, 4);
            $commits[] = compact('hash', 'author', 'date', 'message');
        }
        $output['recent_commits'] = $commits;
        
        // Active branches
        $process = Process::fromShellCommandline('git branch -a');
        $process->run();
        $branches = [];
        foreach(explode("\n", $process->getOutput()) as $branch) {
            if (trim($branch)) {
                $branches[] = trim(str_replace('*', '', $branch));
            }
        }
        $output['branches'] = $branches;
        
        // Get versions
        $process = Process::fromShellCommandline('git tag');
        $process->run();
        $output['versions'] = array_filter(explode("\n", $process->getOutput()));
        
        // Roadmap and feature tracking
        if (file_exists(base_path('roadmap.json'))) {
            $output['roadmap'] = json_decode(file_get_contents(base_path('roadmap.json')), true);
        }
        
        if (file_exists(base_path('features.json'))) {
            $output['features'] = json_decode(file_get_contents(base_path('features.json')), true);
        }
        
        // Write report
        $reportFile = 'reports/git_status_' . date('Y-m-d_His') . '.json';
        Storage::disk('local')->put($reportFile, json_encode($output, JSON_PRETTY_PRINT));
        
        $this->info("Git monitoring report generated: {$reportFile}");
        return 0;
    }
}
```

Add to your scheduler in `App/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    $schedule->command('git:monitor')->hourly();
}
```

### Step 6: Pre-commit Validation Hooks

- Automated testing before commit
- Migration verification
- Version tracking enforcement

```bash
#!/bin/bash
# .git/hooks/pre-commit (make executable with chmod +x)

# Run Laravel tests before commit
php artisan test --parallel

if [ $? -ne 0 ]; then
  echo "Tests failed. Commit aborted."
  exit 1
fi

# Run migration status check
echo "Checking for uncommitted migrations..."
./scripts/ensure-migrations.sh

# Version validation
if [ -f version.json ]; then
  if [[ -n $(git diff --cached --name-only | grep -v "version.json") && -z $(git diff --cached --name-only | grep "version.json") ]]; then
    echo "Warning: Changes detected but version.json not updated"
    read -p "Continue anyway? (y/n) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
      exit 1
    fi
  fi
fi

exit 0
```

### Step 7: Restoration Process

Clear documentation for project restoration:

```markdown
# Restoration Guide for MailZila

## To restore code to a specific version:
1. `git checkout v1.0.0` (replace with your version tag)

## To restore database:
1. `php artisan migrate:fresh`
2. `mysql -u username -p mailzila < backups/snapshots/v1.0.0/mailzila_v1.0.0_20230101_120000.sql`

## To restore complete snapshot including storage:
1. `git checkout tags/v1.0.0`
2. Restore the corresponding database backup
3. `tar -xzf backups/snapshots/v1.0.0/storage_20230101_120000.tar.gz -C ./`
4. Copy the backed up .env file: `cp backups/snapshots/v1.0.0/.env.backup .env`
5. Clear caches: `php artisan optimize:clear`
```

### Step 8: AI Assistant Integration

This step integrates an AI assistant into the development workflow to provide continuous support for project management, version control, and roadmap adherence.

#### AI Assistant Responsibilities

- **Roadmap Monitoring**
  - Track roadmap phase progress
  - Provide regular status updates on milestone completion
  - Suggest adjustments to phase timelines based on progress
  - Reference ID: `AI-RM-001`

- **Version Control Support**
  - Guide branch creation and management
  - Assist with conflict resolution
  - Advise on semantic versioning decisions
  - Audit commit messages and content
  - Reference ID: `AI-VC-002`

- **Feature Implementation Tracking**
  - Monitor feature progress against roadmap
  - Suggest feature organization and prioritization
  - Assist with feature documentation
  - Reference ID: `AI-FT-003`

- **Backup and Restoration Assistance**
  - Provide regular backup reminders
  - Assist with restoration procedures
  - Verify backup integrity
  - Reference ID: `AI-BR-004`

- **Change Management**
  - Track system-wide impact of changes
  - Ensure proper documentation of changes
  - Guide version increment process
  - Reference ID: `AI-CM-005`

- **Regular Check-ins**
  - Perform weekly project status reviews
  - Identify potential roadblocks
  - Suggest workflow optimization
  - Help prioritize development tasks
  - Reference ID: `AI-RC-006`

```json
// ai-assistant.json configuration
{
  "assistant": {
    "name": "MailZila Dev Assistant",
    "version": "1.0.0",
    "responsibilities": [
      {"id": "AI-RM-001", "type": "roadmap_monitoring", "check_frequency": "daily"},
      {"id": "AI-VC-002", "type": "version_control", "check_frequency": "continuous"},
      {"id": "AI-FT-003", "type": "feature_tracking", "check_frequency": "daily"},
      {"id": "AI-BR-004", "type": "backup_restore", "check_frequency": "weekly"},
      {"id": "AI-CM-005", "type": "change_management", "check_frequency": "continuous"},
      {"id": "AI-RC-006", "type": "regular_checkin", "check_frequency": "weekly", "day": "Monday"},
      {"id": "AI-GT-007", "type": "git_workflow", "check_frequency": "daily"},
      {"id": "AI-DB-008", "type": "database_monitoring", "check_frequency": "daily"},
      {"id": "AI-SC-009", "type": "security_audit", "check_frequency": "weekly"},
      {"id": "AI-DX-010", "type": "developer_experience", "check_frequency": "weekly"}
    ],
    "created_at": "2023-08-01",
    "updated_at": "2023-08-01"
  }
}
```

## Roadmap Phases

This section outlines the current and planned project phases. Each phase can be referenced in commit messages, branches, and documentation using its reference ID.

### Phase 1: Initial Setup `[REF:P1-SETUP]` (Current)
- Set up version control system
- Implement backup and restore procedures
- Establish roadmap tracking
- **AI Assistant Tasks**: 
  - Help implement version control scripts `[REF:AI-VC-002]`
  - Establish initial backup procedures `[REF:AI-BR-004]`
  - Set up roadmap tracking system `[REF:AI-RM-001]`

### Phase 2: Core Features `[REF:P2-CORE]`
- *To be defined*
- **AI Assistant Tasks**: 
  - Track feature implementation `[REF:AI-FT-003]`
  - Monitor version control adherence `[REF:AI-VC-002]`

### Phase 3: Enhanced Functionality `[REF:P3-ENHANCED]`
- *To be defined*
- **AI Assistant Tasks**: 
  - Assist with feature organization `[REF:AI-FT-003]`
  - Support change management process `[REF:AI-CM-005]`

### Phase 4: Optimization & Scaling `[REF:P4-SCALE]`
- *To be defined*
- **AI Assistant Tasks**: 
  - Help analyze system performance `[REF:AI-RC-006]`
  - Support version increments based on optimizations `[REF:AI-VC-002]`

## Implementation Notes

Each roadmap phase can be extended with additional details by creating a corresponding Markdown file in the `docs/roadmap/` directory:

```
docs/
└── roadmap/
    ├── phase1-setup.md
    ├── phase2-core.md
    └── ...
```

Reference tasks and phases in commit messages using the reference IDs:

```
git commit -m "Add user authentication system [REF:P2-CORE]"
```

Link AI assistant tasks to specific features or milestones:

```
# Example entry in features.json
{
  "name": "User Authentication",
  "phase": "P2-CORE",
  "ai_tasks": ["AI-FT-003", "AI-CM-005"]
}
``` 