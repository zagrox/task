#!/bin/bash

# Fix Task IDs Script
# This script fixes task IDs by ensuring proper sequential numbering and updates the next_id

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
TASKS_FILE="$SCRIPT_DIR/tasks.json"
BACKUP_DIR="$SCRIPT_DIR/backups"
BACKUP_FILE="$BACKUP_DIR/tasks.json.bak-$(date +"%Y%m%d%H%M%S")"

# Check if jq is installed
if ! command -v jq &> /dev/null; then
    echo "Error: jq is required but not installed."
    echo "Please install jq: https://stedolan.github.io/jq/download/"
    exit 1
fi

# Create backup
echo "Creating backup at $BACKUP_FILE..."
mkdir -p "$BACKUP_DIR"
cp "$TASKS_FILE" "$BACKUP_FILE"

# Check current version from version.json
CURRENT_VERSION=$(jq -r '.major|tostring + "." + .minor|tostring + "." + .patch|tostring' "$SCRIPT_DIR/../version.json")
echo "Current system version: $CURRENT_VERSION"

# Extract tasks and identify non-sequential or abnormal IDs
echo "Analyzing tasks for ID issues..."
TASKS_COUNT=$(jq '.tasks | length' "$TASKS_FILE")
echo "Total tasks: $TASKS_COUNT"

# Get the highest task ID
HIGHEST_ID=$(jq '.tasks | map(.id) | max' "$TASKS_FILE")
echo "Highest task ID: $HIGHEST_ID"

# Get the next_id value
NEXT_ID=$(jq '.next_id' "$TASKS_FILE")
echo "Current next_id: $NEXT_ID"

# Check for ID gaps or abnormally high IDs (more than 10 from the expected sequence)
EXPECTED_MAX_ID=$TASKS_COUNT
ABNORMAL_IDS=$(jq -c '.tasks[] | select(.id > '"$EXPECTED_MAX_ID"' + 10 or .id == 999 or .id == 1000)' "$TASKS_FILE")

if [ -n "$ABNORMAL_IDS" ]; then
    echo "Found abnormal task IDs that need fixing..."
    
    # Create a temporary file with the adjusted tasks
    jq -c '.tasks | to_entries | map(
        if .value.id > '$EXPECTED_MAX_ID' + 10 or .value.id == 999 or .value.id == 1000 then 
            .value += {"id": (.key + '$NEXT_ID' - .index)} 
        else 
            .value 
        end
    ) | map(.value)' "$TASKS_FILE" > /tmp/adjusted_tasks.json
    
    # Update version information for all tasks to current version
    jq -c 'map(if .version == "1.0.0" then . + {"version": "'"$CURRENT_VERSION"'"} else . end)' /tmp/adjusted_tasks.json > /tmp/fixed_tasks.json
    
    # Update the tasks file with fixed tasks and next_id
    NEW_NEXT_ID=$((TASKS_COUNT + 1))
    jq --slurpfile tasks /tmp/fixed_tasks.json '.tasks = $tasks[0] | .next_id = '"$NEW_NEXT_ID" "$TASKS_FILE" > /tmp/fixed_tasks_file.json
    
    # Update metadata
    COMPLETED_COUNT=$(jq '.tasks | map(select(.status == "completed")) | length' /tmp/fixed_tasks_file.json)
    USER_COUNT=$(jq '.tasks | map(select(.assignee == "user")) | length' /tmp/fixed_tasks_file.json)
    AI_COUNT=$(jq '.tasks | map(select(.assignee == "ai")) | length' /tmp/fixed_tasks_file.json)
    
    jq '.metadata.total_tasks = '"$TASKS_COUNT"' | 
        .metadata.completed_tasks = '"$COMPLETED_COUNT"' | 
        .metadata.user_tasks = '"$USER_COUNT"' | 
        .metadata.ai_tasks = '"$AI_COUNT"' | 
        .metadata.last_updated = "'"$(date -u +"%Y-%m-%dT%H:%M:%SZ")"'"' /tmp/fixed_tasks_file.json > /tmp/final_tasks.json
    
    # Replace original file
    mv /tmp/final_tasks.json "$TASKS_FILE"
    
    echo "Task IDs have been fixed successfully."
    echo "All abnormal task IDs have been renumbered."
    echo "Task versions have been updated to $CURRENT_VERSION where needed."
    echo "Next ID is set to $NEW_NEXT_ID."
else
    echo "No abnormal task IDs found. Only checking next_id..."
    
    # Still ensure next_id is correct (greater than highest ID)
    if [ "$NEXT_ID" -le "$HIGHEST_ID" ]; then
        NEW_NEXT_ID=$((HIGHEST_ID + 1))
        echo "Fixing next_id from $NEXT_ID to $NEW_NEXT_ID"
        
        jq '.next_id = '"$NEW_NEXT_ID" "$TASKS_FILE" > /tmp/fixed_next_id.json
        mv /tmp/fixed_next_id.json "$TASKS_FILE"
    else
        echo "next_id is already correct."
    fi
fi

echo "Task ID verification completed."
echo "Backup saved to $BACKUP_FILE"

# Clean up temporary files
rm -f /tmp/adjusted_tasks.json /tmp/fixed_tasks.json /tmp/fixed_tasks_file.json 2>/dev/null

exit 0 