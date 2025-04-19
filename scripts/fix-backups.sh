#!/bin/bash
# scripts/fix-backups.sh
# Purpose: Fix missing database backups by copying from the previous version

# Set variables
VERSION_FILE="version.json"
BACKUP_DIR="backups"

# Check if jq is installed
if ! command -v jq &> /dev/null; then
  echo "Error: jq is required for this script to work."
  echo "Install jq with: brew install jq"
  exit 1
fi

# Get the current version
CURRENT_VERSION=$(jq -r '"\(.major).\(.minor).\(.patch)"' "$VERSION_FILE")
echo "Current version: $CURRENT_VERSION"

# Get all backup versions from backups array in version.json
BACKED_UP_VERSIONS=$(jq -r '.backups[].version' "$VERSION_FILE")
echo "Versions with backups in version.json:"
echo "$BACKED_UP_VERSIONS"

# Get all version history entries
ALL_VERSIONS=$(jq -r '.history[].version' "$VERSION_FILE")
echo -e "\nAll versions in history:"
echo "$ALL_VERSIONS"

# Find missing backups
echo -e "\nChecking for missing backups..."
MISSING_BACKUPS=()
for version in $ALL_VERSIONS; do
  if ! echo "$BACKED_UP_VERSIONS" | grep -q "$version"; then
    # Check if backup directory exists but is empty
    if [ -d "$BACKUP_DIR/v$version" ]; then
      if [ -z "$(ls -A "$BACKUP_DIR/v$version")" ]; then
        echo "Empty backup directory found for v$version"
        MISSING_BACKUPS+=("$version")
      fi
    else
      echo "No backup directory found for v$version"
      MISSING_BACKUPS+=("$version")
    fi
  fi
done

# Fix missing backups
if [ ${#MISSING_BACKUPS[@]} -eq 0 ]; then
  echo "No missing backups found. All versions have backups."
  exit 0
fi

echo -e "\nFixing missing backups..."
for missing_version in "${MISSING_BACKUPS[@]}"; do
  echo "Fixing backup for v$missing_version..."
  
  # Find the closest previous version with a backup
  PREV_VERSION=""
  for version in $BACKED_UP_VERSIONS; do
    if [ "$(printf "%s\n%s" "$version" "$missing_version" | sort -V | head -n1)" = "$version" ] && [ "$version" != "$missing_version" ]; then
      PREV_VERSION="$version"
    fi
  done
  
  if [ -z "$PREV_VERSION" ]; then
    echo "No previous version with backup found for v$missing_version, skipping..."
    continue
  fi
  
  echo "Using backup from v$PREV_VERSION as base..."
  
  # Create backup directory if it doesn't exist
  mkdir -p "$BACKUP_DIR/v$missing_version"
  
  # Copy backup file from previous version
  SOURCE_BACKUP_FILE=$(jq -r --arg ver "$PREV_VERSION" '.backups[] | select(.version == $ver) | .path' "$VERSION_FILE")
  TARGET_BACKUP_FILE="$BACKUP_DIR/v$missing_version/mailzila_v$missing_version.sqlite"
  
  if [ -f "$SOURCE_BACKUP_FILE" ]; then
    cp "$SOURCE_BACKUP_FILE" "$TARGET_BACKUP_FILE"
    echo "✅ Copied backup from $SOURCE_BACKUP_FILE to $TARGET_BACKUP_FILE"
    
    # Add backup entry to version.json
    TODAY=$(date +"%Y-%m-%d")
    jq --arg version "$missing_version" \
       --arg date "$TODAY" \
       --arg path "$TARGET_BACKUP_FILE" \
       '.backups += [{version: $version, date: $date, path: $path}]' "$VERSION_FILE" > "${VERSION_FILE}.tmp" && \
       mv "${VERSION_FILE}.tmp" "$VERSION_FILE"
    
    echo "✅ Added backup entry to version.json for v$missing_version"
  else
    echo "❌ Source backup file $SOURCE_BACKUP_FILE not found"
  fi
done

echo -e "\nBackup fix complete!" 