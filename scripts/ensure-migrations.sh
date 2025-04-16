#!/bin/bash
# scripts/ensure-migrations.sh
# Purpose: Ensure database changes include migrations

# Cache file for optimization
CACHE_FILE=".migration_cache"
CURRENT_MIGRATIONS=$(find database/migrations -type f | sort)

# Check if database model files were changed
if [[ -n $(git diff --cached --name-only | grep -E "app/Models|database/migrations") ]]; then
  # Check if migrations were included
  if [[ -n $(git diff --cached --name-only | grep "database/migrations") ]]; then
    echo "✅ Migrations detected in commit"
    # Update migration cache
    echo "$CURRENT_MIGRATIONS" > "$CACHE_FILE"
    exit 0
  else
    echo "❌ Warning: Database model changes detected but no migrations found"
    read -p "Continue anyway? (y/n) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
      exit 1
    fi
  fi
fi

exit 0 