#!/bin/bash
# scripts/fix-backups.sh
# Purpose: Fix missing database backups in version directories

set -e

# Configuration
VERSION_FILE="version.json"
DB_FILE="database/database.sqlite"

# Check if required files exist
if [ ! -f "$VERSION_FILE" ]; then
  echo "Error: version.json not found"
  exit 1
fi

if [ ! -f "$DB_FILE" ]; then
  echo "Error: Database file not found at $DB_FILE"
  exit 1
fi

# Function to create a backup for a specific version
create_backup_for_version() {
  local version=$1
  local backup_dir="backups/v$version"
  local backup_file="$backup_dir/mailzila_v${version}.sqlite"
  
  echo "Checking backup for version $version..."
  
  # Create backup directory if it doesn't exist
  mkdir -p "$backup_dir"
  
  # Check if backup file already exists
  if [ -f "$backup_file" ]; then
    echo "✅ Backup already exists for v$version"
    return 0
  fi
  
  echo "Creating backup for v$version..."
  
  # Copy the current database to the backup location
  cp "$DB_FILE" "$backup_file"
  
  if [ $? -eq 0 ]; then
    echo "✅ Created database backup for v$version"
    # Add backup info to version.json if using jq
    if command -v jq &> /dev/null; then
      if ! jq -e '.backups' "$VERSION_FILE" > /dev/null 2>&1; then
        # Add backups array if it doesn't exist
        jq '. + {backups:[]}' "$VERSION_FILE" > "${VERSION_FILE}.tmp" && mv "${VERSION_FILE}.tmp" "$VERSION_FILE"
      fi
      
      # Add backup entry if it doesn't exist already
      if ! jq -e ".backups[] | select(.version == \"$version\")" "$VERSION_FILE" > /dev/null 2>&1; then
        jq --arg version "$version" \
           --arg date "$(date +"%Y-%m-%d")" \
           --arg path "$backup_file" \
           '.backups += [{version: $version, date: $date, path: $path}]' "$VERSION_FILE" > "${VERSION_FILE}.tmp" && \
           mv "${VERSION_FILE}.tmp" "$VERSION_FILE"
        echo "✅ Updated version.json with backup information"
      fi
    fi
    return 0
  else
    echo "❌ Failed to create backup for v$version"
    return 1
  fi
}

# Get all version tags from Git
echo "Checking Git tags for versions..."
git_versions=$(git tag --list "v*" | sort -V)

# Get all versions from the version.json file
echo "Checking version.json for version history..."
if command -v jq &> /dev/null; then
  json_versions=$(jq -r '.history[].version' "$VERSION_FILE" | sort -V)
else
  # Fallback if jq is not available
  json_versions=$(grep -o '"version": "[^"]*"' "$VERSION_FILE" | cut -d'"' -f4 | sort -V)
fi

# Get existing backup directories
echo "Checking existing backup directories..."
backup_dirs=$(find backups -type d -name "v*" | sed 's/backups\/v//g' | sort -V)

# Combine all unique versions
all_versions=$(printf "%s\n%s\n%s\n" "$git_versions" "$json_versions" "$backup_dirs" | sed 's/^v//g' | sort -V | uniq)

# Create backups for each version
echo "Checking backups for all versions..."
for version in $all_versions; do
  create_backup_for_version "$version"
done

# Fix empty backup directories
echo "Checking for empty backup directories..."
empty_dirs=$(find backups -type d -name "v*" -empty | sort)
if [ -n "$empty_dirs" ]; then
  echo "Found empty backup directories:"
  for dir in $empty_dirs; do
    version=$(echo "$dir" | sed 's/backups\/v//g')
    echo "  - $dir (v$version)"
    create_backup_for_version "$version"
  done
else
  echo "No empty backup directories found."
fi

echo "✅ Backup check and fix completed. All versions should now have database backups." 