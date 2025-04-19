#!/bin/bash
# scripts/release.sh
# Purpose: Manage version releases with automatic backups

if [ $# -lt 1 ]; then
  echo "Usage: ./release.sh [major|minor|patch] \"Release notes\""
  exit 1
fi

TYPE=$1
NOTES=$2
VERSION_FILE="version.json"

# Check if jq is installed
if ! command -v jq &> /dev/null; then
  echo "Warning: jq is not installed. Using alternative method for version management."
  echo "For optimal performance, install jq with: brew install jq"
  
  # Simple version management without jq
  if [ -f "$VERSION_FILE" ]; then
    # Extract version components using grep and sed
    MAJOR=$(grep -o '"major":[0-9]*' "$VERSION_FILE" | grep -o '[0-9]*')
    MINOR=$(grep -o '"minor":[0-9]*' "$VERSION_FILE" | grep -o '[0-9]*')
    PATCH=$(grep -o '"patch":[0-9]*' "$VERSION_FILE" | grep -o '[0-9]*')
    
    # Default values if not found
    MAJOR=${MAJOR:-0}
    MINOR=${MINOR:-1}
    PATCH=${PATCH:-0}
    
    # Increment version
    if [ "$TYPE" == "major" ]; then
      MAJOR=$((MAJOR + 1))
      MINOR=0
      PATCH=0
    elif [ "$TYPE" == "minor" ]; then
      MINOR=$((MINOR + 1))
      PATCH=0
    else
      PATCH=$((PATCH + 1))
    fi
    
    VERSION="$MAJOR.$MINOR.$PATCH"
    
    # Create a simplified version file
    echo "{
  \"major\": $MAJOR,
  \"minor\": $MINOR,
  \"patch\": $PATCH,
  \"history\": [
    {
      \"version\": \"$VERSION\",
      \"date\": \"$(date +"%Y-%m-%d")\",
      \"notes\": \"$NOTES\"
    }
  ]
}" > "$VERSION_FILE"
  else
    # Create initial version file
    if [ "$TYPE" == "major" ]; then
      VERSION="1.0.0"
    elif [ "$TYPE" == "minor" ]; then
      VERSION="0.1.0"
    else
      VERSION="0.0.1"
    fi
    
    echo "{
  \"major\": ${VERSION%%.*},
  \"minor\": ${VERSION#*.},
  \"patch\": ${VERSION##*.},
  \"history\": [
    {
      \"version\": \"$VERSION\",
      \"date\": \"$(date +"%Y-%m-%d")\",
      \"notes\": \"$NOTES\"
    }
  ]
}" > "$VERSION_FILE"
  fi
else
  # Original jq-based version management
  # Create version file if it doesn't exist
  if [ ! -f "$VERSION_FILE" ]; then
    echo '{"major":0,"minor":1,"patch":0,"history":[],"backups":[],"indexes":{"by_date":{},"by_version":{}}}' > "$VERSION_FILE"
  fi

  # Increment version with optimized structure
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
  CURRENT_DATE=$(date +"%Y-%m-%d")

  # Add to history with index updates
  TMP=$(mktemp)
  jq --arg version "$VERSION" \
     --arg date "$CURRENT_DATE" \
     --arg notes "$NOTES" \
     '.history += [{"version":$version,"date":$date,"notes":$notes}] |
      .indexes.by_date[$date] = (.history | length - 1) |
      .indexes.by_version[$version] = (.history | length - 1)' \
     "$VERSION_FILE" > "$TMP" && mv "$TMP" "$VERSION_FILE"
fi

# Create database backup
BACKUP_DIR="backups/v$VERSION"
BACKUP_FILE="$BACKUP_DIR/mailzila_v${VERSION}.sqlite"
mkdir -p "$BACKUP_DIR"
BACKUP_SUCCESS=false

# Function to log backup status
log_backup_status() {
  if [ "$BACKUP_SUCCESS" = true ]; then
    echo "✅ Database backup created at $BACKUP_FILE"
    
    # Add backup entry to version.json if jq is available
    if command -v jq &> /dev/null; then
      if ! jq -e '.backups' "$VERSION_FILE" > /dev/null 2>&1; then
        # Add backups array if it doesn't exist
        jq '. + {backups:[]}' "$VERSION_FILE" > "${VERSION_FILE}.tmp" && mv "${VERSION_FILE}.tmp" "$VERSION_FILE"
      fi
      
      # Add backup entry
      jq --arg version "$VERSION" \
         --arg date "$CURRENT_DATE" \
         --arg path "$BACKUP_FILE" \
         '.backups += [{version: $version, date: $date, path: $path}]' "$VERSION_FILE" > "${VERSION_FILE}.tmp" && \
         mv "${VERSION_FILE}.tmp" "$VERSION_FILE"
    fi
  else
    echo "⚠️ Could not create database backup with standard methods"
  fi
}

# Try Laravel's artisan dump command first
if command -v php &> /dev/null && [ -f "artisan" ]; then
  echo "Attempting database backup with Laravel..."
  if php artisan schema:dump --database="$BACKUP_FILE" 2>/dev/null; then
    BACKUP_SUCCESS=true
    echo "✅ Database backup created with Laravel schema:dump"
  elif php artisan db:dump --database="$BACKUP_FILE" 2>/dev/null; then
    BACKUP_SUCCESS=true
    echo "✅ Database backup created with Laravel db:dump"
  else
    echo "Laravel backup methods failed, trying mysqldump..."
    
    # Try mysqldump
    if command -v mysqldump &> /dev/null; then
      # Get database info from .env
      if [ -f ".env" ]; then
        DB_CONNECTION=$(grep DB_CONNECTION .env | cut -d '=' -f2)
        DB_DATABASE=$(grep DB_DATABASE .env | cut -d '=' -f2)
        DB_USERNAME=$(grep DB_USERNAME .env | cut -d '=' -f2)
        DB_PASSWORD=$(grep DB_PASSWORD .env | cut -d '=' -f2)
        
        if [ "$DB_CONNECTION" = "sqlite" ]; then
          echo "SQLite database detected, creating backup by copying database file..."
          if [ -f "database/database.sqlite" ]; then
            cp "database/database.sqlite" "$BACKUP_FILE"
            if [ $? -eq 0 ]; then
              BACKUP_SUCCESS=true
              echo "✅ SQLite database backup created by file copy"
            else
              echo "❌ SQLite database backup failed"
            fi
          else
            echo "❌ SQLite database file not found"
          fi
        elif [ -n "$DB_DATABASE" ] && [ -n "$DB_USERNAME" ]; then
          echo "MySQL database detected, creating backup using mysqldump..."
          mysqldump -u "$DB_USERNAME" --password="$DB_PASSWORD" "$DB_DATABASE" > "${BACKUP_FILE}.sql"
          if [ $? -eq 0 ]; then
            BACKUP_SUCCESS=true
            echo "✅ MySQL database backup created with mysqldump"
          else
            echo "❌ MySQL backup failed"
          fi
        else
          echo "Warning: Could not get database credentials from .env"
        fi
      else
        echo "Warning: No .env file found for database credentials"
      fi
    else
      echo "Warning: Neither Laravel nor mysqldump available"
    fi
  fi
else
  echo "Laravel not detected, trying direct database backup..."
  
  # Try direct file copy for SQLite
  if [ -f "database/database.sqlite" ]; then
    echo "SQLite database detected, creating backup by copying database file..."
    cp "database/database.sqlite" "$BACKUP_FILE"
    if [ $? -eq 0 ]; then
      BACKUP_SUCCESS=true
      echo "✅ SQLite database backup created by file copy"
    else
      echo "❌ SQLite database backup failed"
    fi
  else
    echo "SQLite database file not found, checking for MySQL..."
    
    # Try mysqldump
    if command -v mysqldump &> /dev/null && [ -f ".env" ]; then
      DB_DATABASE=$(grep DB_DATABASE .env | cut -d '=' -f2)
      DB_USERNAME=$(grep DB_USERNAME .env | cut -d '=' -f2)
      DB_PASSWORD=$(grep DB_PASSWORD .env | cut -d '=' -f2)
      
      if [ -n "$DB_DATABASE" ] && [ -n "$DB_USERNAME" ]; then
        mysqldump -u "$DB_USERNAME" --password="$DB_PASSWORD" "$DB_DATABASE" > "${BACKUP_FILE}.sql"
        if [ $? -eq 0 ]; then
          BACKUP_SUCCESS=true
          echo "✅ MySQL database backup created with mysqldump"
        else
          echo "❌ MySQL backup failed"
        fi
      else
        echo "Warning: Could not get database credentials from .env"
      fi
    else
      echo "No database backup method available"
    fi
  fi
fi

# Log backup status
log_backup_status

# Update Laravel version if config exists
if [ -f "config/app.php" ]; then
  sed -i '' "s/'version' => '.*'/'version' => '$VERSION'/g" config/app.php 2>/dev/null
  if [ $? -ne 0 ]; then
    echo "Note: Could not update version in config/app.php"
  fi
fi

# Update app.php to include version if it doesn't exist
if ! grep -q "'version'" "config/app.php"; then
  TMP=$(mktemp)
  awk '
  /'\''name'\'' =>/ { print; print "    '\''version'\'' => '\'''"$VERSION"''\'',";\
    next }
  { print }
  ' config/app.php > "$TMP" && mv "$TMP" config/app.php
fi

# Commit and tag
echo "Creating release commit and tag for v$VERSION..."
git add "$VERSION_FILE" config/app.php 2>/dev/null
git commit -m "Release v$VERSION"
git tag -a "v$VERSION" -m "$NOTES"

echo "Released v$VERSION"
echo "Don't forget to push: git push origin main --tags" 