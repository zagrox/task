# MailZila GitHub Version Management Strategy

This document outlines the version management strategy for syncing MailZila versions with GitHub. This strategy ensures that all 7 versions are properly tracked, tagged, and managed in the GitHub repository.

## Overview

The version management system synchronizes:
- Version information from `version.json`
- Git tags for each version
- Git branches for major versions
- GitHub releases
- Version backups

## Strategy Components

1. **Version Tracking**:
   - Central source of truth: `version.json` in project root
   - Semantic versioning (MAJOR.MINOR.PATCH)
   - Version history with release notes

2. **Git Integration**:
   - Git tags for each version (e.g., `v1.0.7`)
   - Version-specific branches (e.g., `version/v1.0.7`)
   - Automated tag and branch creation

3. **GitHub Releases**:
   - GitHub releases for each version
   - Release notes from `version.json`
   - Assets and documentation

4. **Backup System**:
   - Version-specific backups in `/backups` directory
   - Database migration snapshots
   - Rollback capability

## Implementation Guide

### Getting Started

1. Make the script executable:
```bash
chmod +x scripts/version-github-sync.sh
```

2. Initialize all versions:
```bash
./scripts/version-github-sync.sh init
```

This will:
- Create Git tags for all versions in `version.json`
- Create backups for each version
- Push tags to GitHub (if they don't exist)

### Managing Versions

#### Syncing a Specific Version

```bash
./scripts/version-github-sync.sh sync 1.0.7
```

This will:
- Check if the version exists in `version.json`
- Create a Git tag if it doesn't exist
- Create a backup
- Push the tag to GitHub

#### Force Syncing a Version

```bash
./scripts/version-github-sync.sh sync 1.0.7 force
```

#### Creating GitHub Releases

```bash
./scripts/version-github-sync.sh release 1.0.7 YOUR_GITHUB_TOKEN
```

This will create a GitHub release for version 1.0.7 with release notes from `version.json`.

#### Managing Version Branches

```bash
./scripts/version-github-sync.sh branches
```

This will:
- Create a branch for each version (e.g., `version/v1.0.7`)
- Update `composer.json` version
- Create a VERSION file
- Push branches to GitHub

#### Viewing Version Information

```bash
# Show current version
./scripts/version-github-sync.sh current

# List all versions
./scripts/version-github-sync.sh list
```

## Best Practices

1. **Version Creation**:
   - Always update `version.json` when creating a new version
   - Include detailed release notes
   - Follow semantic versioning principles

2. **Commit Workflow**:
   - Finish all changes before creating a new version
   - Run version sync after updating `version.json`
   - Push all tags and branches to GitHub

3. **Release Management**:
   - Create GitHub releases for major/minor versions
   - Include detailed release notes and migration guides
   - Attach relevant assets to releases

4. **Backup Strategy**:
   - Keep backups of each version
   - Include database migrations in backups
   - Test restoration process periodically

## Integration with CI/CD

The version management can be integrated with CI/CD pipelines:

```yaml
# Example GitHub Actions workflow
name: Version Management

on:
  push:
    paths:
      - 'version.json'

jobs:
  sync-version:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
        with:
          fetch-depth: 0
      
      - name: Set up environment
        run: |
          sudo apt-get update
          sudo apt-get install -y jq
      
      - name: Sync version
        run: |
          ./scripts/version-github-sync.sh sync
      
      - name: Create GitHub Release
        if: success()
        run: |
          ./scripts/version-github-sync.sh release ${{ secrets.GITHUB_TOKEN }}
```

## Version History Management

For the 7 existing versions (1.0.1 through 1.0.7), the script:
1. Creates Git tags for each version
2. Creates version-specific branches
3. Preserves backup snapshots
4. Synchronizes all information with GitHub
5. Maintains version history in `version.json`

## Rollback Procedure

To rollback to a previous version:

1. Checkout the version tag or branch:
```bash
git checkout v1.0.6
# or
git checkout version/v1.0.6
```

2. Copy necessary files from backup:
```bash
cp -r backups/v1.0.6/* .
```

3. Update version.json to reflect the current version.

## Conclusion

This version management strategy provides a robust system for:
- Tracking MailZila versions
- Integrating with GitHub
- Managing releases
- Maintaining backups
- Supporting rollback procedures

By implementing this strategy, you ensure that all 7 versions of MailZila are properly tracked in Git and synchronized with GitHub. 