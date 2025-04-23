#!/bin/bash

# Task ID Validation Script
# Validates task IDs and ensures they are sequential, with correct next_id
# Author: AI Assistant
# Date: 2025-04-21

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
TASKS_FILE="$SCRIPT_DIR/tasks.json"
BACKUP_DIR="$SCRIPT_DIR/backups"
BACKUP_FILE="$BACKUP_DIR/tasks.json.bak-$(date +"%Y%m%d%H%M%S")"
LOG_FILE="$SCRIPT_DIR/task-validation.log"

# Check if jq is installed
if ! command -v jq &> /dev/null; then
    echo "Error: jq is required but not installed."
    echo "Please install jq: https://stedolan.github.io/jq/download/"
    exit 1
fi

# Log function
log() {
    echo "$(date): $1" | tee -a "$LOG_FILE"
}

log "=== Task ID Validation Started ==="

# Create backup directory if it doesn't exist
mkdir -p "$BACKUP_DIR"

# Create backup before any potential changes
cp "$TASKS_FILE" "$BACKUP_FILE"
log "Created backup at $BACKUP_FILE"

# Get current version from version.json
CURRENT_VERSION=$(jq -r '.major|tostring + "." + .minor|tostring + "." + .patch|tostring' "$SCRIPT_DIR/../version.json" 2>/dev/null || echo "Unknown")
log "Current system version: $CURRENT_VERSION"

# Check for duplicate IDs
log "Checking for duplicate IDs..."
DUPLICATE_IDS=$(jq -r '.tasks[].id' "$TASKS_FILE" | sort -n | uniq -d)
if [ -n "$DUPLICATE_IDS" ]; then
    log "WARNING: Duplicate IDs found: $DUPLICATE_IDS"
    # We'll fix this below
fi

# Count tasks and get highest ID
TASK_COUNT=$(jq '.tasks | length' "$TASKS_FILE")
HIGHEST_ID=$(jq '.tasks | map(.id) | max' "$TASKS_FILE")
NEXT_ID=$(jq '.next_id' "$TASKS_FILE")

log "Task count: $TASK_COUNT"
log "Highest ID: $HIGHEST_ID"
log "Current next_id: $NEXT_ID"

# Check for gap between highest ID and next_id
if [ "$NEXT_ID" -le "$HIGHEST_ID" ]; then
    NEW_NEXT_ID=$((HIGHEST_ID + 1))
    log "WARNING: next_id ($NEXT_ID) is not greater than highest ID ($HIGHEST_ID)"
    
    # Fix next_id
    jq ".next_id = $NEW_NEXT_ID" "$TASKS_FILE" > "$TASKS_FILE.tmp" && \
    mv "$TASKS_FILE.tmp" "$TASKS_FILE"
    
    log "Fixed next_id to $NEW_NEXT_ID"
fi

# Check for non-sequential IDs or abnormal IDs (e.g., 999, 1000)
log "Checking for non-sequential or abnormal IDs..."

# Extract task IDs and sort them
IDS=$(jq -r '.tasks[].id' "$TASKS_FILE" | sort -n)
ID_COUNT=$(echo "$IDS" | wc -l | tr -d ' ')

# Check if any IDs are more than 10 higher than expected
PROBLEM_IDS=()
HIGHEST_EXPECTED=$ID_COUNT
INDEX=0

for ID in $IDS; do
    # Check if ID is more than 10 higher than its index position
    if [ "$ID" -gt $((INDEX + HIGHEST_EXPECTED + 10)) ]; then
        PROBLEM_IDS+=("$ID")
    fi
    
    # Check for special problematic IDs
    if [ "$ID" -eq 999 ] || [ "$ID" -eq 1000 ] && [ "$ID" -gt $HIGHEST_EXPECTED ]; then
        PROBLEM_IDS+=("$ID")
    fi
    
    INDEX=$((INDEX + 1))
done

# Fix problem IDs if found
if [ ${#PROBLEM_IDS[@]} -gt 0 ]; then
    log "WARNING: Problem IDs found: ${PROBLEM_IDS[*]}"
    log "Fixing task IDs..."
    
    # Create temporary copy for modification
    cp "$TASKS_FILE" "$TASKS_FILE.working"
    
    # For each problem ID, find that task and update its ID and version
    for PROBLEM_ID in "${PROBLEM_IDS[@]}"; do
        # Find a new ID starting from next_id
        NEW_ID=$NEXT_ID
        NEXT_ID=$((NEXT_ID + 1))
        
        # Update the task ID
        jq --arg pid "$PROBLEM_ID" --arg nid "$NEW_ID" --arg ver "$CURRENT_VERSION" \
           '.tasks = (.tasks | map(if .id == ($pid | tonumber) then .id = ($nid | tonumber) | .version = $ver else . end))' \
           "$TASKS_FILE.working" > "$TASKS_FILE.tmp" && \
        mv "$TASKS_FILE.tmp" "$TASKS_FILE.working"
        
        log "Changed task ID from $PROBLEM_ID to $NEW_ID"
    done
    
    # Update next_id to new value
    jq ".next_id = $NEXT_ID" "$TASKS_FILE.working" > "$TASKS_FILE.tmp" && \
    mv "$TASKS_FILE.tmp" "$TASKS_FILE.working"
    
    # Update the original file
    mv "$TASKS_FILE.working" "$TASKS_FILE"
    
    log "Task IDs have been fixed. New next_id is $NEXT_ID"
else
    log "All task IDs are within acceptable range."
fi

# Finally, ensure all tasks have the correct version
if [ "$CURRENT_VERSION" != "Unknown" ]; then
    log "Checking for tasks with old version numbers..."
    
    OUTDATED_TASKS=$(jq --arg ver "$CURRENT_VERSION" '.tasks[] | select(.version != $ver) | .id' "$TASKS_FILE")
    
    if [ -n "$OUTDATED_TASKS" ]; then
        log "Updating version for tasks: $OUTDATED_TASKS"
        
        # Update version for all tasks with wrong version
        jq --arg ver "$CURRENT_VERSION" '.tasks = (.tasks | map(if .version != $ver then .version = $ver else . end))' \
           "$TASKS_FILE" > "$TASKS_FILE.tmp" && \
        mv "$TASKS_FILE.tmp" "$TASKS_FILE"
        
        log "Updated task versions to $CURRENT_VERSION"
    else
        log "All tasks have the correct version."
    fi
fi

log "Task ID validation completed successfully."

# Add this script to git hooks
if [ -d "$SCRIPT_DIR/../.git/hooks" ]; then
    log "Adding pre-commit hook to validate task IDs..."
    
    # Create pre-commit hook if it doesn't exist
    PRE_COMMIT_HOOK="$SCRIPT_DIR/../.git/hooks/pre-commit"
    
    if [ ! -f "$PRE_COMMIT_HOOK" ]; then
        cat > "$PRE_COMMIT_HOOK" << 'EOL'
#!/bin/bash

# Run task validation script before commit
PROJECT_ROOT=$(git rev-parse --show-toplevel)
TASKS_SCRIPT="$PROJECT_ROOT/project-management/validate-task-ids.sh"

if [ -f "$TASKS_SCRIPT" ]; then
    echo "Running task ID validation..."
    bash "$TASKS_SCRIPT"
    
    # Check for changes in tasks.json after validation
    if git diff --name-only | grep -q "tasks.json"; then
        echo "Task IDs were fixed. Please add the changes and commit again."
        exit 1
    fi
fi

exit 0
EOL
        chmod +x "$PRE_COMMIT_HOOK"
        log "Created pre-commit hook at $PRE_COMMIT_HOOK"
    elif ! grep -q "validate-task-ids.sh" "$PRE_COMMIT_HOOK"; then
        # Append to existing pre-commit hook
        cat >> "$PRE_COMMIT_HOOK" << 'EOL'

# Run task validation script before commit
PROJECT_ROOT=$(git rev-parse --show-toplevel)
TASKS_SCRIPT="$PROJECT_ROOT/project-management/validate-task-ids.sh"

if [ -f "$TASKS_SCRIPT" ]; then
    echo "Running task ID validation..."
    bash "$TASKS_SCRIPT"
    
    # Check for changes in tasks.json after validation
    if git diff --name-only | grep -q "tasks.json"; then
        echo "Task IDs were fixed. Please add the changes and commit again."
        exit 1
    fi
fi
EOL
        log "Added task validation to existing pre-commit hook"
    else
        log "Pre-commit hook already contains task validation"
    fi
fi

# Set the script as executable
chmod +x "$0"

log "=== Task ID Validation Finished ==="
exit 0
