#!/bin/bash
# scripts/rollback.sh
# Purpose: Rollback to a previous version with database restoration

if [ $# -lt 1 ]; then
  echo "Usage: ./rollback.sh <version-to-rollback-to> [--force]"
  echo "Example: ./rollback.sh 1.0.4"
  exit 1
fi

TARGET_VERSION=$1
FORCE=$2
VERSION_FILE="version.json"
BACKUP_FOUND=false

# Function to check if version exists in previous_versions
version_exists() {
  grep -q "\"$TARGET_VERSION\"" "$VERSION_FILE"
  return $?
}

# Function to get current version
get_current_version() {
  grep -o '"version": "[^"]*"' "$VERSION_FILE" | cut -d '"' -f 4
}

# Check if target version exists in previous versions
if ! version_exists; then
  echo "Error: Version $TARGET_VERSION is not found in previous versions."
  echo "Available previous versions:"
  grep -o '"previous_versions": \[[^]]*\]' "$VERSION_FILE" | sed 's/.*\[\([^]]*\)\].*/\1/' | tr ',' '\n' | sed 's/^ *"\([^"]*\)".*/\1/'
  exit 1
fi

# Check if target version is different from current version
CURRENT_VERSION=$(get_current_version)
if [ "$CURRENT_VERSION" == "$TARGET_VERSION" ]; then
  echo "Warning: Target version $TARGET_VERSION is the same as current version."
  if [ "$FORCE" != "--force" ]; then
    echo "Use --force to proceed anyway."
    exit 1
  fi
fi

# Check for Git 
if ! command -v git &> /dev/null; then
  echo "Error: Git is required for rollback operations."
  exit 1
fi

# Check for Git tag
if ! git tag | grep -q "v$TARGET_VERSION"; then
  echo "Warning: Git tag v$TARGET_VERSION not found."
  if [ "$FORCE" != "--force" ]; then
    echo "Using --force will proceed without Git, just updating version.json and config files."
    exit 1
  fi
fi

# Detect database type from .env
DB_CONNECTION=$(grep DB_CONNECTION .env | grep -v "^#" | cut -d '=' -f2)
echo "Detected database connection: $DB_CONNECTION"

# Look for database backup based on connection type
BACKUP_DIR="backups/v$TARGET_VERSION"
if [ "$DB_CONNECTION" == "sqlite" ]; then
  BACKUP_FILE="$BACKUP_DIR/mailzila_v${TARGET_VERSION}.sqlite"
  SQLITE_DB_PATH="database/database.sqlite"
else
  BACKUP_FILE="$BACKUP_DIR/mailzila_v${TARGET_VERSION}.sql"
fi

if [ -d "$BACKUP_DIR" ] && [ -f "$BACKUP_FILE" ]; then
  BACKUP_FOUND=true
  echo "Database backup found at: $BACKUP_FILE"
else
  echo "Warning: No database backup found for version $TARGET_VERSION"
  echo "Looking for alternative backup locations..."
  
  # Search for version-specific database backups
  if [ "$DB_CONNECTION" == "sqlite" ]; then
    ALT_BACKUP=$(find backups -name "*$TARGET_VERSION*.sqlite" -type f | head -n 1)
  else
    ALT_BACKUP=$(find backups -name "*$TARGET_VERSION*.sql" -type f | head -n 1)
  fi
  
  if [ -n "$ALT_BACKUP" ]; then
    BACKUP_FOUND=true
    BACKUP_FILE=$ALT_BACKUP
    echo "Alternative database backup found at: $BACKUP_FILE"
  else
    if [ "$FORCE" != "--force" ]; then
      echo "No database backup found. Use --force to proceed without database restoration."
      exit 1
    fi
    echo "Proceeding without database restoration."
  fi
fi

# Confirm rollback
if [ "$FORCE" != "--force" ]; then
  echo "You are about to rollback to version $TARGET_VERSION."
  read -p "This operation will reset code and potentially restore database. Continue? (y/n) " -n 1 -r
  echo
  if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "Rollback cancelled."
    exit 1
  fi
fi

# Create a backup of the current state before rollback
echo "Creating backup of current state..."
CURRENT_BACKUP_DIR="backups/snapshots/pre_rollback_$(date +"%Y%m%d_%H%M%S")"
mkdir -p "$CURRENT_BACKUP_DIR"

# Database backup based on connection type
if [ "$DB_CONNECTION" == "sqlite" ]; then
  # SQLite backup
  if [ -f "$SQLITE_DB_PATH" ]; then
    cp "$SQLITE_DB_PATH" "$CURRENT_BACKUP_DIR/mailzila_v${CURRENT_VERSION}_pre_rollback.sqlite"
    echo "Current SQLite database backed up."
  else
    echo "Warning: SQLite database not found at $SQLITE_DB_PATH"
  fi
else
  # MySQL/PostgreSQL backup
  if command -v php &> /dev/null && [ -f "artisan" ]; then
    php artisan db:dump --database="$CURRENT_BACKUP_DIR/mailzila_v${CURRENT_VERSION}_pre_rollback.sql" 2>/dev/null
    if [ $? -ne 0 ]; then
      echo "Laravel db:dump failed, trying mysqldump..."
      if command -v mysqldump &> /dev/null; then
        # Get database info from .env
        if [ -f ".env" ]; then
          DB_DATABASE=$(grep DB_DATABASE .env | cut -d '=' -f2)
          DB_USERNAME=$(grep DB_USERNAME .env | cut -d '=' -f2)
          DB_PASSWORD=$(grep DB_PASSWORD .env | cut -d '=' -f2)
          
          if [ -n "$DB_DATABASE" ] && [ -n "$DB_USERNAME" ]; then
            mysqldump -u "$DB_USERNAME" --password="$DB_PASSWORD" "$DB_DATABASE" > "$CURRENT_BACKUP_DIR/mailzila_v${CURRENT_VERSION}_pre_rollback.sql"
            echo "Current database backup created with mysqldump"
          else
            echo "Warning: Could not get database credentials from .env"
          fi
        else
          echo "Warning: No .env file found for database credentials"
        fi
      else
        echo "Warning: Current database could not be backed up"
      fi
    else
      echo "Current database backup created with Laravel db:dump"
    fi
  fi
fi

# Save current version.json
cp "$VERSION_FILE" "$CURRENT_BACKUP_DIR/version.json.bak"

echo "Performing rollback to v$TARGET_VERSION..."

# Git-based rollback if available
if git tag | grep -q "v$TARGET_VERSION" && [ "$FORCE" != "--force-no-git" ]; then
  echo "Rolling back code to Git tag v$TARGET_VERSION..."
  
  # Check for uncommitted changes
  if [ -n "$(git status --porcelain)" ]; then
    echo "Warning: You have uncommitted changes."
    if [ "$FORCE" != "--force" ]; then
      echo "Please commit or stash your changes first, or use --force to proceed."
      exit 1
    fi
    echo "Proceeding with uncommitted changes. They may be lost."
  fi
  
  # Checkout the tag
  git checkout "v$TARGET_VERSION"
  
  if [ $? -ne 0 ]; then
    echo "Error: Git checkout failed."
    exit 1
  fi
else
  echo "Skipping Git-based code rollback, updating version.json only..."
  
  # Getting version components from target version
  IFS='.' read -r MAJOR MINOR PATCH <<< "$TARGET_VERSION"
  
  # Update version.json without jq
  VERSION_TMP=$(mktemp)
  sed "s/\"version\": \"$CURRENT_VERSION\"/\"version\": \"$TARGET_VERSION\"/" "$VERSION_FILE" > "$VERSION_TMP"
  sed "s/\"major\": [0-9]*/\"major\": $MAJOR/" "$VERSION_TMP" > "$VERSION_FILE"
  sed "s/\"minor\": [0-9]*/\"minor\": $MINOR/" "$VERSION_FILE" > "$VERSION_TMP"
  sed "s/\"patch\": [0-9]*/\"patch\": $PATCH/" "$VERSION_TMP" > "$VERSION_FILE"
  rm "$VERSION_TMP"
  echo "Updated version.json to $TARGET_VERSION"
fi

# Update Laravel version if config exists
if [ -f "config/app.php" ]; then
  sed -i '' "s/'version' => '.*'/'version' => '$TARGET_VERSION'/g" config/app.php 2>/dev/null
  if [ $? -ne 0 ]; then
    echo "Note: Could not update version in config/app.php"
  else
    echo "Updated version in config/app.php"
  fi
fi

# Restore database if backup found
if [ "$BACKUP_FOUND" = true ]; then
  echo "Restoring database from backup..."
  
  if [ "$DB_CONNECTION" == "sqlite" ]; then
    # SQLite restore is just a file copy
    if [ -f "$BACKUP_FILE" ]; then
      cp "$BACKUP_FILE" "$SQLITE_DB_PATH"
      if [ $? -eq 0 ]; then
        echo "SQLite database restored successfully."
      else
        echo "Error: Failed to restore SQLite database."
      fi
    else
      echo "Error: SQLite backup file not found: $BACKUP_FILE"
    fi
  else
    # MySQL/PostgreSQL restore
    # Get database info from .env
    if [ -f ".env" ]; then
      DB_DATABASE=$(grep DB_DATABASE .env | cut -d '=' -f2)
      DB_USERNAME=$(grep DB_USERNAME .env | cut -d '=' -f2)
      DB_PASSWORD=$(grep DB_PASSWORD .env | cut -d '=' -f2)
      
      if [ -n "$DB_DATABASE" ] && [ -n "$DB_USERNAME" ]; then
        # Try import via artisan first if available
        if command -v php &> /dev/null && [ -f "artisan" ] && php artisan | grep -q "db:import"; then
          php artisan db:import --source="$BACKUP_FILE"
          if [ $? -eq 0 ]; then
            echo "Database restored with Laravel db:import"
          else
            echo "Laravel db:import failed, trying mysql import..."
            mysql -u "$DB_USERNAME" --password="$DB_PASSWORD" "$DB_DATABASE" < "$BACKUP_FILE"
            if [ $? -eq 0 ]; then
              echo "Database restored with mysql import"
            else
              echo "Error: Database restoration failed"
            fi
          fi
        else
          # Fallback to direct mysql import
          mysql -u "$DB_USERNAME" --password="$DB_PASSWORD" "$DB_DATABASE" < "$BACKUP_FILE"
          if [ $? -eq 0 ]; then
            echo "Database restored with mysql import"
          else
            echo "Error: Database restoration failed"
          fi
        fi
      else
        echo "Error: Missing database credentials in .env file"
      fi
    else
      echo "Error: .env file not found for database credentials"
    fi
  fi
fi

# Clear Laravel cache if applicable
if command -v php &> /dev/null && [ -f "artisan" ]; then
  echo "Clearing application cache..."
  php artisan cache:clear
  php artisan config:clear
  php artisan view:clear
  php artisan route:clear
fi

echo "Rollback to v$TARGET_VERSION completed."
if [ "$BACKUP_FOUND" = true ]; then
  echo "Database has been restored from backup."
else
  echo "No database backup was restored."
fi

echo "You may need to run: composer install"
echo "and: npm install (if using Node.js)" 