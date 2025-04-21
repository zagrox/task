#!/bin/bash

# Fix Task IDs Script
# This script fixes duplicate task IDs and ensures proper sorting and ID assignment

TASKS_FILE="tasks.json"
BACKUP_DIR="backups"
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

# Sort tasks by creation date
echo "Extracting tasks and sorting by creation date..."
jq -c '.tasks[]' "$TASKS_FILE" | jq -s 'sort_by(.created_at)' > sorted_tasks.json

# Re-assign task IDs sequentially
echo "Re-assigning task IDs sequentially..."
jq -c 'to_entries | map(.value += {"id": (.key + 1)}) | map(.value)' sorted_tasks.json > renumbered_tasks.json

# Count total tasks for next_id
TASK_COUNT=$(jq 'length' renumbered_tasks.json)
NEXT_ID=$((TASK_COUNT + 1))
echo "Total tasks: $TASK_COUNT, Next ID will be: $NEXT_ID"

# Update the tasks file with new IDs
echo "Updating tasks.json with fixed IDs..."
jq --argjson tasks "$(cat renumbered_tasks.json)" --argjson next_id "$NEXT_ID" \
   '.tasks = $tasks | .next_id = $next_id' "$TASKS_FILE" > tmp_tasks.json

# Update metadata
echo "Updating metadata..."
COMPLETED=$(jq '.tasks[] | select(.status == "completed") | .id' tmp_tasks.json | wc -l | tr -d ' ')
USER_TASKS=$(jq '.tasks[] | select(.assignee == "user") | .id' tmp_tasks.json | wc -l | tr -d ' ')
AI_TASKS=$(jq '.tasks[] | select(.assignee == "ai") | .id' tmp_tasks.json | wc -l | tr -d ' ')

jq --argjson total "$TASK_COUNT" \
   --argjson completed "$COMPLETED" \
   --argjson user_tasks "$USER_TASKS" \
   --argjson ai_tasks "$AI_TASKS" \
   --arg updated "$(date -u +"%Y-%m-%dT%H:%M:%SZ")" \
   '.metadata.total_tasks = $total |
    .metadata.completed_tasks = $completed |
    .metadata.user_tasks = $user_tasks |
    .metadata.ai_tasks = $ai_tasks |
    .metadata.last_updated = $updated' tmp_tasks.json > "$TASKS_FILE"

# Clean up temporary files
rm sorted_tasks.json renumbered_tasks.json tmp_tasks.json

echo "Task IDs have been fixed successfully."
echo "All tasks now have unique IDs from 1 to $TASK_COUNT."
echo "Next ID is set to $NEXT_ID."
echo ""
echo "Updates applied to $TASKS_FILE"
echo "Backup saved to $BACKUP_FILE"

# Modify task-manager.sh to prevent duplicate IDs in the future
TASK_MANAGER="task-manager.sh"
if [ -f "$TASK_MANAGER" ]; then
    echo "Updating task-manager.sh to prevent future ID issues..."
    
    # Create a backup of task-manager.sh
    cp "$TASK_MANAGER" "$TASK_MANAGER.bak"
    
    # Add a function to validate next_id before adding tasks
    sed -i.bak '/update_metadata()/a\
\
# Validate and fix next_id if needed\
validate_next_id() {\
    # Find highest ID currently in use\
    local highest_id=$(jq '"'"'.tasks[].id | max // 0'"'"' "$TASKS_FILE")\
    # Ensure next_id is greater than highest_id\
    local current_next_id=$(jq '"'"'.next_id'"'"' "$TASKS_FILE")\
    \
    if [[ $current_next_id -le $highest_id ]]; then\
        local new_next_id=$((highest_id + 1))\
        echo "Warning: Fixing inconsistent next_id. Was $current_next_id, now $new_next_id"\
        \
        jq --argjson new_id "$new_next_id" '"'"'.next_id = $new_id'"'"' "$TASKS_FILE" > "$TASKS_FILE.tmp" && \
        mv "$TASKS_FILE.tmp" "$TASKS_FILE"\
    fi\
}' "$TASK_MANAGER"

    # Add validate_next_id call before adding a task
    sed -i.bak 's/# Get the next ID/# Validate next_id\n    validate_next_id\n\n    # Get the next ID/' "$TASK_MANAGER"
    
    echo "Task manager script has been updated to prevent duplicate IDs."
else
    echo "Warning: task-manager.sh file not found. Manual update required."
fi

# Add a periodic ID validation to ensure all future operations maintain ID integrity
# Create an automatic ID checker that runs weekly
cat > "validate-task-ids.sh" << 'EOL'
#!/bin/bash

# Weekly task ID validation script
# Add this to cron with: 0 0 * * 0 /path/to/validate-task-ids.sh

TASKS_FILE="$(dirname "$0")/tasks.json"

# Check for duplicate IDs
DUPLICATES=$(jq '.tasks[].id' "$TASKS_FILE" | sort | uniq -d)
if [ ! -z "$DUPLICATES" ]; then
    echo "Duplicate IDs found: $DUPLICATES"
    echo "Running fix-task-ids.sh to resolve issues..."
    "$(dirname "$0")/fix-task-ids.sh"
fi

# Verify next_id
HIGHEST_ID=$(jq '.tasks[].id | max // 0' "$TASKS_FILE")
NEXT_ID=$(jq '.next_id' "$TASKS_FILE")
if [ "$NEXT_ID" -le "$HIGHEST_ID" ]; then
    NEW_NEXT_ID=$((HIGHEST_ID + 1))
    echo "Invalid next_id: $NEXT_ID (should be > $HIGHEST_ID)"
    jq --argjson new_id "$NEW_NEXT_ID" '.next_id = $new_id' "$TASKS_FILE" > "$TASKS_FILE.tmp" && \
    mv "$TASKS_FILE.tmp" "$TASKS_FILE"
    echo "Fixed next_id to: $NEW_NEXT_ID"
fi
EOL

chmod +x "validate-task-ids.sh"
echo "Created weekly validation script: validate-task-ids.sh"
echo "Consider adding this to cron for periodic validation." 