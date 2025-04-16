#!/bin/bash
# scripts/backup.sh
# Purpose: Create comprehensive backups with performance optimizations

# Check if jq is installed
if ! command -v jq &> /dev/null; then
  echo "Error: jq is required but not installed."
  echo "Install with: brew install jq"
  exit 1
fi

# Get current version - if version.json exists and has version info
if [ -f "version.json" ]; then
  VERSION=$(jq -r '"\(.major).\(.minor).\(.patch)"' version.json 2>/dev/null)
  if [ $? -ne 0 ] || [ -z "$VERSION" ] || [ "$VERSION" == "null.null.null" ]; then
    # Fallback to date-based version if version.json is invalid
    VERSION="snapshot-$(date +"%Y%m%d")"
  fi
else
  # Use date-based version if no version.json
  VERSION="snapshot-$(date +"%Y%m%d")"
fi

# Create timestamp with high precision for uniqueness
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
BACKUP_DIR="backups/snapshots/v$VERSION"

# Make sure backup directory exists
mkdir -p "$BACKUP_DIR"

# Flag to track if any backup method succeeded
BACKUP_SUCCESS=false

# Try to backup database - first try Laravel, then direct MySQL
if command -v php &> /dev/null && [ -f "artisan" ]; then
  echo "Attempting database backup with Laravel..."
  php artisan db:dump --database="$BACKUP_DIR/mailzila_v${VERSION}_${TIMESTAMP}.sql" 2>/dev/null
  
  if [ $? -eq 0 ]; then
    echo "✅ Database backup created with Laravel"
    BACKUP_SUCCESS=true
  else
    echo "Laravel backup failed, trying direct MySQL backup..."
    
    # Try MySQL backup if Laravel failed
    if [ -f ".env" ] && command -v mysqldump &> /dev/null; then
      DB_DATABASE=$(grep DB_DATABASE .env | cut -d '=' -f2)
      DB_USERNAME=$(grep DB_USERNAME .env | cut -d '=' -f2)
      DB_PASSWORD=$(grep DB_PASSWORD .env | cut -d '=' -f2)
      
      if [ -n "$DB_DATABASE" ] && [ -n "$DB_USERNAME" ]; then
        echo "Backing up MySQL database $DB_DATABASE..."
        
        # Use compression for performance if available
        if command -v gzip &> /dev/null; then
          mysqldump -u "$DB_USERNAME" --password="$DB_PASSWORD" "$DB_DATABASE" | gzip > "$BACKUP_DIR/mailzila_v${VERSION}_${TIMESTAMP}.sql.gz"
          if [ ${PIPESTATUS[0]} -eq 0 ]; then
            echo "✅ Database backup created with mysqldump and compressed"
            BACKUP_SUCCESS=true
          else
            echo "❌ MySQL backup failed"
          fi
        else
          mysqldump -u "$DB_USERNAME" --password="$DB_PASSWORD" "$DB_DATABASE" > "$BACKUP_DIR/mailzila_v${VERSION}_${TIMESTAMP}.sql"
          if [ $? -eq 0 ]; then
            echo "✅ Database backup created with mysqldump"
            BACKUP_SUCCESS=true
          else
            echo "❌ MySQL backup failed"
          fi
        fi
      else
        echo "❌ Could not read database credentials from .env"
      fi
    else
      echo "❌ Neither Laravel db:dump nor mysqldump available"
    fi
  fi
else
  echo "⚠️ No Laravel or MySQL tools found for database backup"
fi

# Backup .env file if it exists
if [ -f ".env" ]; then
  cp .env "$BACKUP_DIR/.env.backup"
  echo "✅ Environment file backed up"
  BACKUP_SUCCESS=true
else
  echo "⚠️ No .env file found to backup"
fi

# Backup storage files if directory exists
if [ -d "storage/app" ]; then
  echo "Backing up storage files..."
  
  # Use incremental backup if previous backup exists
  PREV_BACKUP=$(find "$BACKUP_DIR" -name "storage_*.tar.gz" | sort -r | head -n 1)
  
  if [ -n "$PREV_BACKUP" ] && command -v tar &> /dev/null && command -v find &> /dev/null; then
    # Get the timestamp of the most recent backup for comparison
    PREV_TIMESTAMP=$(stat -f %m "$PREV_BACKUP")
    
    # Only backup files newer than previous backup
    echo "Creating incremental backup since previous backup..."
    find storage/app -type f -newermt "@$PREV_TIMESTAMP" -print0 | tar -czf "$BACKUP_DIR/storage_${TIMESTAMP}_incr.tar.gz" --null -T -
    
    if [ $? -eq 0 ] && [ -s "$BACKUP_DIR/storage_${TIMESTAMP}_incr.tar.gz" ]; then
      echo "✅ Incremental storage backup created"
      BACKUP_SUCCESS=true
    else
      # If incremental backup failed or is empty, do full backup
      echo "Incremental backup empty or failed, creating full backup..."
      tar -czf "$BACKUP_DIR/storage_${TIMESTAMP}.tar.gz" storage/app
      
      if [ $? -eq 0 ]; then
        echo "✅ Full storage backup created"
        BACKUP_SUCCESS=true
      else
        echo "❌ Storage backup failed"
      fi
    fi
  else
    # Do full backup if no previous backup or tools missing
    tar -czf "$BACKUP_DIR/storage_${TIMESTAMP}.tar.gz" storage/app
    
    if [ $? -eq 0 ]; then
      echo "✅ Full storage backup created"
      BACKUP_SUCCESS=true
    else
      echo "❌ Storage backup failed"
    fi
  fi
else
  echo "⚠️ No storage/app directory found to backup"
fi

# Create metadata file
echo "Backup created: $TIMESTAMP" > "$BACKUP_DIR/metadata.txt"
echo "Version: $VERSION" >> "$BACKUP_DIR/metadata.txt"
echo "Git commit: $(git rev-parse HEAD 2>/dev/null || echo 'unknown')" >> "$BACKUP_DIR/metadata.txt"
echo "Backup type: $([ -n "$PREV_BACKUP" ] && echo 'incremental' || echo 'full')" >> "$BACKUP_DIR/metadata.txt"

# Add backup record to version.json if it exists and is valid
if [ -f "version.json" ]; then
  echo "Updating version.json with backup record..."
  TMP=$(mktemp)
  
  # Try to add backup record, handling potential errors in the JSON
  jq --arg time "$TIMESTAMP" \
     --arg path "$BACKUP_DIR" \
     --arg success "$BACKUP_SUCCESS" \
     '(.backups = if has("backups") then .backups else [] end) |
      .backups += [{"timestamp":$time,"path":$path,"success":$success}]' \
     version.json > "$TMP" 2>/dev/null
  
  if [ $? -eq 0 ]; then
    mv "$TMP" version.json
    git add version.json
    git commit -m "Add backup metadata for $TIMESTAMP" 2>/dev/null
    echo "✅ Backup metadata added to version.json"
  else
    echo "⚠️ Could not update version.json, might be invalid JSON"
    rm "$TMP"
  fi
else
  echo "⚠️ No version.json found, skipping backup metadata recording"
fi

# Report backup results
if [ "$BACKUP_SUCCESS" = true ]; then
  echo "✅ Backup completed successfully to: $BACKUP_DIR"
  echo "Timestamp: $TIMESTAMP"
else
  echo "❌ Backup process completed with ERRORS"
fi

# Optional - try to upload to remote storage if rclone is configured
if command -v rclone &> /dev/null; then
  echo "Checking for remote storage destinations..."
  if rclone listremotes | grep -q "mailzila-backups:"; then
    echo "Uploading backup to remote storage..."
    rclone copy "$BACKUP_DIR" mailzila-backups:v$VERSION
    if [ $? -eq 0 ]; then
      echo "✅ Uploaded to remote storage"
    else
      echo "❌ Remote storage upload failed"
    fi
  else
    echo "⚠️ No mailzila-backups remote configured in rclone"
  fi
else
  echo "ℹ️ rclone not found, skipping remote backup"
fi 