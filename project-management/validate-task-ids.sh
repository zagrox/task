#!/bin/bash

# Weekly task ID validation script
# Add this to cron with: 0 0 * * 0 /path/to/validate-task-ids.sh

TASKS_FILE="$(dirname "$0")/tasks.json"
BACKUP_DIR="$(dirname "$0")/backups"
BACKUP_FILE="$BACKUP_DIR/tasks.json.bak-$(date +"%Y%m%d%H%M%S")"

# Check if jq is installed
if ! command -v jq &> /dev/null; then
    echo "Error: jq is required but not installed."
    echo "Please install jq: https://stedolan.github.io/jq/download/"
    exit 1
fi

echo "=== Task ID Validation $(date) ==="

# Create backup before any potential changes
mkdir -p "$BACKUP_DIR"
cp "$TASKS_FILE" "$BACKUP_FILE"
echo "Backup created at $BACKUP_FILE"

# Check for duplicate IDs
echo "Checking for duplicate IDs..."
DUPLICATES=$(jq '.tasks[].id' "$TASKS_FILE" | sort | uniq -d)
if [ ! -z "$DUPLICATES" ]; then
    echo "Duplicate IDs found: $DUPLICATES"
    echo "Running fix-task-ids.sh to resolve issues..."
    "$(dirname "$0")/fix-task-ids.sh"
else
    echo "No duplicate IDs found."
fi

# Verify next_id
echo "Verifying next_id value..."
TASK_COUNT=$(jq '.tasks | length' "$TASKS_FILE")

if [ "$TASK_COUNT" -gt 0 ]; then
    HIGHEST_ID=$(jq '.tasks | map(.id) | max' "$TASKS_FILE")
    NEXT_ID=$(jq '.next_id' "$TASKS_FILE")
    
    if [ "$NEXT_ID" -le "$HIGHEST_ID" ]; then
        NEW_NEXT_ID=$((HIGHEST_ID + 1))
        echo "Invalid next_id: $NEXT_ID (should be > $HIGHEST_ID)"
        jq --argjson new_id "$NEW_NEXT_ID" '.next_id = $new_id' "$TASKS_FILE" > "$TASKS_FILE.tmp" && \
        mv "$TASKS_FILE.tmp" "$TASKS_FILE"
        echo "Fixed next_id to: $NEW_NEXT_ID"
    else
        echo "next_id is valid: $NEXT_ID"
    fi
else
    echo "No tasks found. next_id should be 1."
    jq '.next_id = 1' "$TASKS_FILE" > "$TASKS_FILE.tmp" && \
    mv "$TASKS_FILE.tmp" "$TASKS_FILE"
fi

# Verify task ID sequence (should be unique integers)
echo "Verifying task ID sequence..."
EXPECTED_COUNT=$(jq '.tasks | length' "$TASKS_FILE")
ACTUAL_COUNT=$(jq '.tasks[].id' "$TASKS_FILE" | sort -n | uniq | wc -l | tr -d ' ' 2>/dev/null || echo 0)

if [ "$EXPECTED_COUNT" -ne "$ACTUAL_COUNT" ] && [ "$EXPECTED_COUNT" -gt 0 ]; then
    echo "Inconsistent task count detected: Found $ACTUAL_COUNT unique IDs but $EXPECTED_COUNT tasks"
    echo "Running fix-task-ids.sh to ensure IDs are properly assigned..."
    "$(dirname "$0")/fix-task-ids.sh"
else
    echo "Task IDs are unique and consistent."
fi

echo "Task ID validation completed successfully."

# Add this to crontab by running:
# crontab -e
# Then add this line to run every Sunday at midnight:
# 0 0 * * 0 /path/to/task/project-management/validate-task-ids.sh >> /path/to/task/project-management/task-validation.log 2>&1
