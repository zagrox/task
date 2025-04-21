#!/bin/bash

# Task Task Management System
# A lightweight JSON-based task management system for Task project

TASKS_FILE="$(dirname "$0")/tasks.json"
BACKUP_DIR="$(dirname "$0")/backups"

# Check if jq is installed
if ! command -v jq &> /dev/null; then
    echo "Error: jq is required but not installed."
    echo "Please install jq: https://stedolan.github.io/jq/download/"
    exit 1
fi

# Ensure the tasks file exists
if [ ! -f "$TASKS_FILE" ]; then
    echo "Creating a new tasks file..."
    mkdir -p "$(dirname "$TASKS_FILE")"
    echo '{
      "metadata": {
        "total_tasks": 0,
        "completed_tasks": 0,
        "user_tasks": 0,
        "ai_tasks": 0,
        "last_updated": "'$(date -u +"%Y-%m-%dT%H:%M:%SZ")'"
      },
      "next_id": 1,
      "tasks": []
    }' > "$TASKS_FILE"
fi

# Ensure the backup directory exists
mkdir -p "$BACKUP_DIR"

# Create a backup of the tasks file
create_backup() {
    local backup_file="$BACKUP_DIR/tasks_$(date +"%Y%m%d_%H%M%S").json"
    cp "$TASKS_FILE" "$backup_file"
    echo "Backup created: $backup_file"
}

# Update metadata in the tasks file
update_metadata() {
    local total=$(jq '.tasks | length' "$TASKS_FILE")
    local completed=$(jq '.tasks[] | select(.status == "completed") | .id' "$TASKS_FILE" | wc -l)
    local user_tasks=$(jq '.tasks[] | select(.assignee == "user") | .id' "$TASKS_FILE" | wc -l)
    local ai_tasks=$(jq '.tasks[] | select(.assignee == "ai") | .id' "$TASKS_FILE" | wc -l)
    local now=$(date -u +"%Y-%m-%dT%H:%M:%SZ")
    
    jq --arg now "$now" \
       --argjson total "$total" \
       --argjson completed "$completed" \
       --argjson user_tasks "$user_tasks" \
       --argjson ai_tasks "$ai_tasks" \
       '.metadata.total_tasks = $total | 
        .metadata.completed_tasks = $completed | 
        .metadata.user_tasks = $user_tasks | 
        .metadata.ai_tasks = $ai_tasks | 
        .metadata.last_updated = $now' "$TASKS_FILE" > "$TASKS_FILE.tmp" && 
    mv "$TASKS_FILE.tmp" "$TASKS_FILE"
}

# Validate and fix next_id if needed
validate_next_id() {
    # Find highest ID currently in use
    local highest_id=$(jq '.tasks[].id | max // 0' "$TASKS_FILE")
    # Ensure next_id is greater than highest_id
    local current_next_id=$(jq '.next_id' "$TASKS_FILE")
    
    if [[ $current_next_id -le $highest_id ]]; then
        local new_next_id=$((highest_id + 1))
        echo "Warning: Fixing inconsistent next_id. Was $current_next_id, now $new_next_id"
        
        jq --argjson new_id "$new_next_id" '.next_id = $new_id' "$TASKS_FILE" > "$TASKS_FILE.tmp" && 
        mv "$TASKS_FILE.tmp" "$TASKS_FILE"
    fi
}

# Add a new task
add_task() {
    local title="$1"
    local description="$2"
    local assignee="${3:-user}"
    local priority="${4:-medium}"
    local status="${5:-pending}"
    local due_date="${6:-}"
    local related_feature="${7:-}"
    local related_phase="${8:-}"
    local tags="${9:-}"
    local estimated_hours="${10:-0}"
    
    # Validate required parameters
    if [ -z "$title" ] || [ -z "$description" ]; then
        echo "Error: Title and description are required."
        echo "Usage: $0 add \"Task Title\" \"Task Description\" [assignee] [priority] [status] [due_date] [related_feature] [related_phase] [tags] [estimated_hours]"
        return 1
    fi

    # Create backup before modifying
    create_backup
    
    # Validate next_id
    validate_next_id
    
    # Get the next ID
    local next_id=$(jq '.next_id' "$TASKS_FILE")
    
    # Format tags as a JSON array if provided
    local tags_json="[]"
    if [ ! -z "$tags" ]; then
        # Convert comma-separated tags to JSON array
        tags_json=$(echo "$tags" | jq -R 'split(",") | map(. | gsub("^\\s+|\\s+$"; ""))')
    fi
    
    # Get current date in ISO format
    local now=$(date -u +"%Y-%m-%dT%H:%M:%SZ")
    
    # Add the new task
    jq --arg title "$title" \
       --arg desc "$description" \
       --arg assignee "$assignee" \
       --arg priority "$priority" \
       --arg status "$status" \
       --arg created "$now" \
       --arg updated "$now" \
       --arg due "$due_date" \
       --arg feature "$related_feature" \
       --arg phase "$related_phase" \
       --argjson tags "$tags_json" \
       --arg est "$estimated_hours" \
       --argjson id "$next_id" \
       '.tasks += [{
           "id": $id,
           "title": $title,
           "description": $desc,
           "assignee": $assignee,
           "status": $status,
           "priority": $priority,
           "created_at": $created,
           "updated_at": $updated,
           "due_date": $due,
           "related_feature": $feature,
           "related_phase": $phase,
           "dependencies": [],
           "progress": 0,
           "notes": [],
           "tags": $tags,
           "estimated_hours": ($est | tonumber),
           "actual_hours": 0
       }] | .next_id += 1' "$TASKS_FILE" > "$TASKS_FILE.tmp" && 
    mv "$TASKS_FILE.tmp" "$TASKS_FILE"
    
    # Update metadata
    update_metadata
    
    echo "Task #$next_id added successfully."
}

# List tasks with optional filtering
list_tasks() {
    local filter="$1"
    local value="$2"
    local query=".tasks[]"
    
    # Apply filters if provided
    if [ ! -z "$filter" ] && [ ! -z "$value" ]; then
        case "$filter" in
            status)
                query="$query | select(.status == \"$value\")"
                ;;
            assignee)
                query="$query | select(.assignee == \"$value\")"
                ;;
            priority)
                query="$query | select(.priority == \"$value\")"
                ;;
            feature)
                query="$query | select(.related_feature == \"$value\")"
                ;;
            phase)
                query="$query | select(.related_phase == \"$value\")"
                ;;
            tag)
                query="$query | select(.tags | index(\"$value\") != null)"
                ;;
            due)
                # List tasks due before the specified date
                query="$query | select(.due_date != null and .due_date != \"\" and .due_date <= \"$value\")"
                ;;
            id)
                query="$query | select(.id == $value)"
                ;;
            *)
                echo "Invalid filter: $filter"
                echo "Available filters: status, assignee, priority, feature, phase, tag, due, id"
                return 1
                ;;
        esac
    fi
    
    # Execute the query and format the output
    jq -r "$query | \"#\\(.id): \\(.title) [\\(.status)] - \\(.priority) priority, assigned to \\(.assignee)\\(if .due_date != \"\" and .due_date != null then \", due \\(.due_date)\" else \"\" end)\\(if .progress > 0 then \", \\(.progress)% complete\" else \"\" end)\"" "$TASKS_FILE" | sort -n
    
    # Display task count
    local count=$(jq -r "$query | .id" "$TASKS_FILE" | wc -l | tr -d ' ')
    echo "Total: $count task(s)"
}

# Show detailed information about a specific task
show_task() {
    local id="$1"
    
    if [ -z "$id" ]; then
        echo "Error: Task ID is required."
        echo "Usage: $0 show <task_id>"
        return 1
    fi
    
    # Check if the task exists
    if ! jq -e ".tasks[] | select(.id == $id)" "$TASKS_FILE" > /dev/null; then
        echo "Error: Task #$id not found."
        return 1
    fi
    
    # Display detailed task information
    echo "============ Task #$id ============"
    jq -r ".tasks[] | select(.id == $id) | \"Title: \(.title)
Description: \(.description)
Status: \(.status)
Priority: \(.priority)
Assignee: \(.assignee)
Progress: \(.progress)%
Created: \(.created_at)
Updated: \(.updated_at)
Due Date: \(.due_date // \"Not set\")
Feature: \(.related_feature // \"Not set\")
Phase: \(.related_phase // \"Not set\")
Estimated Hours: \(.estimated_hours)
Actual Hours: \(.actual_hours)
Tags: \(.tags | join(\", \"))
Dependencies: \(.dependencies | join(\", \") // \"None\")\"" "$TASKS_FILE"
    
    # Display notes if any
    local notes_count=$(jq -r ".tasks[] | select(.id == $id) | .notes | length" "$TASKS_FILE")
    if [ "$notes_count" -gt 0 ]; then
        echo "Notes:"
        jq -r ".tasks[] | select(.id == $id) | .notes[] | \"[\(.timestamp)] \(.content)\"" "$TASKS_FILE"
    else
        echo "Notes: None"
    fi
    echo "===================================="
}

# Update a task
update_task() {
    local id="$1"
    local field="$2"
    local value="$3"
    
    # Validate parameters
    if [ -z "$id" ] || [ -z "$field" ] || [ -z "$value" ]; then
        echo "Error: Task ID, field, and value are required."
        echo "Usage: $0 update <task_id> <field> <value>"
        echo "Available fields: title, description, status, priority, assignee, progress, due_date,"
        echo "              related_feature, related_phase, tags, dependencies, estimated_hours, actual_hours"
        return 1
    fi
    
    # Check if the task exists
    if ! jq -e ".tasks[] | select(.id == $id)" "$TASKS_FILE" > /dev/null; then
        echo "Error: Task #$id not found."
        return 1
    fi
    
    # Create backup before modifying
    create_backup
    
    # Update the task based on the field
    case "$field" in
        title|description|status|priority|assignee|due_date|related_feature|related_phase)
            # Simple string fields
            jq --arg id "$id" --arg field "$field" --arg value "$value" --arg now "$(date -u +"%Y-%m-%dT%H:%M:%SZ")" \
               '.tasks = (.tasks | map(if (.id == ($id | tonumber)) then . + {($field): $value, "updated_at": $now} else . end))' \
               "$TASKS_FILE" > "$TASKS_FILE.tmp" && 
            mv "$TASKS_FILE.tmp" "$TASKS_FILE"
            ;;
        progress)
            # Numeric field for progress percentage (0-100)
            if ! [[ "$value" =~ ^[0-9]+$ ]] || [ "$value" -lt 0 ] || [ "$value" -gt 100 ]; then
                echo "Error: Progress must be a number between 0 and 100."
                return 1
            fi
            
            jq --arg id "$id" --argjson value "$value" --arg now "$(date -u +"%Y-%m-%dT%H:%M:%SZ")" \
               '.tasks = (.tasks | map(if (.id == ($id | tonumber)) then . + {"progress": $value, "updated_at": $now} else . end))' \
               "$TASKS_FILE" > "$TASKS_FILE.tmp" && 
            mv "$TASKS_FILE.tmp" "$TASKS_FILE"
            
            # If progress is 100, set status to completed
            if [ "$value" -eq 100 ]; then
                jq --arg id "$id" --arg now "$(date -u +"%Y-%m-%dT%H:%M:%SZ")" \
                   '.tasks = (.tasks | map(if (.id == ($id | tonumber)) then . + {"status": "completed", "updated_at": $now} else . end))' \
                   "$TASKS_FILE" > "$TASKS_FILE.tmp" && 
                mv "$TASKS_FILE.tmp" "$TASKS_FILE"
                echo "Status automatically set to 'completed'."
            fi
            ;;
        tags)
            # Convert comma-separated tags to JSON array
            local tags_json=$(echo "$value" | jq -R 'split(",") | map(. | gsub("^\\s+|\\s+$"; ""))')
            
            jq --arg id "$id" --argjson tags "$tags_json" --arg now "$(date -u +"%Y-%m-%dT%H:%M:%SZ")" \
               '.tasks = (.tasks | map(if (.id == ($id | tonumber)) then . + {"tags": $tags, "updated_at": $now} else . end))' \
               "$TASKS_FILE" > "$TASKS_FILE.tmp" && 
            mv "$TASKS_FILE.tmp" "$TASKS_FILE"
            ;;
        dependencies)
            # Convert comma-separated dependencies to JSON array of integers
            local deps_array="[]"
            if [ ! -z "$value" ]; then
                deps_array=$(echo "$value" | tr ',' '\n' | jq -R '. | tonumber' | jq -s .)
            fi
            
            jq --arg id "$id" --argjson deps "$deps_array" --arg now "$(date -u +"%Y-%m-%dT%H:%M:%SZ")" \
               '.tasks = (.tasks | map(if (.id == ($id | tonumber)) then . + {"dependencies": $deps, "updated_at": $now} else . end))' \
               "$TASKS_FILE" > "$TASKS_FILE.tmp" && 
            mv "$TASKS_FILE.tmp" "$TASKS_FILE"
            ;;
        estimated_hours|actual_hours)
            # Numeric fields for hours
            if ! [[ "$value" =~ ^[0-9]+(\.[0-9]+)?$ ]]; then
                echo "Error: Hours must be a number."
                return 1
            fi
            
            jq --arg id "$id" --arg field "$field" --argjson value "$value" --arg now "$(date -u +"%Y-%m-%dT%H:%M:%SZ")" \
               '.tasks = (.tasks | map(if (.id == ($id | tonumber)) then . + {($field): $value, "updated_at": $now} else . end))' \
               "$TASKS_FILE" > "$TASKS_FILE.tmp" && 
            mv "$TASKS_FILE.tmp" "$TASKS_FILE"
            ;;
        *)
            echo "Error: Invalid field '$field'."
            echo "Available fields: title, description, status, priority, assignee, progress, due_date,"
            echo "              related_feature, related_phase, tags, dependencies, estimated_hours, actual_hours"
            return 1
            ;;
    esac
    
    # Update metadata
        update_metadata
    
    echo "Task #$id updated successfully."
}

# Add a note to a task
add_note() {
    local id="$1"
    local content="$2"
    
    # Validate parameters
    if [ -z "$id" ] || [ -z "$content" ]; then
        echo "Error: Task ID and note content are required."
        echo "Usage: $0 note <task_id> \"Note content\""
        return 1
    fi
    
    # Check if the task exists
    if ! jq -e ".tasks[] | select(.id == $id)" "$TASKS_FILE" > /dev/null; then
        echo "Error: Task #$id not found."
        return 1
    fi
    
    # Create backup before modifying
    create_backup
    
    # Get current date in ISO format
    local now=$(date -u +"%Y-%m-%dT%H:%M:%SZ")
    
    # Add the note
    jq --arg id "$id" --arg content "$content" --arg timestamp "$now" --arg updated "$now" \
       '.tasks = (.tasks | map(if (.id == ($id | tonumber)) then . + {"notes": (.notes + [{"content": $content, "timestamp": $timestamp}]), "updated_at": $updated} else . end))' \
       "$TASKS_FILE" > "$TASKS_FILE.tmp" && 
    mv "$TASKS_FILE.tmp" "$TASKS_FILE"
    
    # Update metadata
    update_metadata
    
    echo "Note added to Task #$id."
}

# Delete a task
delete_task() {
    local id="$1"
    
    # Validate parameters
    if [ -z "$id" ]; then
        echo "Error: Task ID is required."
        echo "Usage: $0 delete <task_id>"
        return 1
    fi
    
    # Check if the task exists
    if ! jq -e ".tasks[] | select(.id == $id)" "$TASKS_FILE" > /dev/null; then
        echo "Error: Task #$id not found."
        return 1
    fi
    
    # Confirm deletion
    read -p "Are you sure you want to delete Task #$id? (y/N) " confirm
    if [[ ! "$confirm" =~ ^[Yy]$ ]]; then
        echo "Deletion cancelled."
        return 0
    fi
    
    # Create backup before modifying
    create_backup
    
    # Remove the task
    jq --arg id "$id" '.tasks = (.tasks | map(select(.id != ($id | tonumber))))' "$TASKS_FILE" > "$TASKS_FILE.tmp" && 
    mv "$TASKS_FILE.tmp" "$TASKS_FILE"
    
    # Update metadata
    update_metadata
    
    echo "Task #$id deleted."
}

# Generate a report of tasks
generate_report() {
    local filter="${1:-}"
    local value="${2:-}"
    
    echo "====================== Task Report ======================"
    echo "Generated: $(date)"
    echo ""
    
    # Get total counts
            local total=$(jq '.tasks | length' "$TASKS_FILE")
            local completed=$(jq '.tasks[] | select(.status == "completed") | .id' "$TASKS_FILE" | wc -l)
    local pending=$(jq '.tasks[] | select(.status == "pending") | .id' "$TASKS_FILE" | wc -l)
            local in_progress=$(jq '.tasks[] | select(.status == "in-progress") | .id' "$TASKS_FILE" | wc -l)
    local blocked=$(jq '.tasks[] | select(.status == "blocked") | .id' "$TASKS_FILE" | wc -l)
            
            echo "Total Tasks: $total"
            echo "Completed: $completed"
    echo "Pending: $pending"
            echo "In Progress: $in_progress"
    echo "Blocked: $blocked"
    echo ""
    
    # Apply filter if provided
    if [ ! -z "$filter" ] && [ ! -z "$value" ]; then
        echo "Filtered by $filter: $value"
        echo ""
        list_tasks "$filter" "$value"
    else
        # Show tasks by status
        echo "--- Completed Tasks ---"
        list_tasks "status" "completed"
        echo ""
        
        echo "--- In Progress Tasks ---"
        list_tasks "status" "in-progress"
        echo ""
        
        echo "--- Pending Tasks ---"
        list_tasks "status" "pending"
        echo ""
        
        echo "--- Blocked Tasks ---"
        list_tasks "status" "blocked"
    fi
    
    echo "========================================================"
}

# Main function to process commands
main() {
    local command="$1"
    shift
    
    case "$command" in
        add)
        add_task "$@"
        ;;
    list)
        list_tasks "$@"
        ;;
    show)
        show_task "$@"
        ;;
    update)
        update_task "$@"
        ;;
    note)
        add_note "$@"
        ;;
    delete)
        delete_task "$@"
        ;;
    report)
        generate_report "$@"
        ;;
        help)
            echo "Usage: $0 <command> [options]"
            echo ""
            echo "Commands:"
            echo "  add <title> <description> [assignee] [priority] [status] [due_date] [feature] [phase] [tags] [est_hours]"
            echo "      Add a new task"
            echo "  list [filter] [value]"
            echo "      List tasks with optional filtering"
            echo "  show <task_id>"
            echo "      Show detailed information about a task"
            echo "  update <task_id> <field> <value>"
            echo "      Update a field in a task"
            echo "  note <task_id> <content>"
            echo "      Add a note to a task"
            echo "  delete <task_id>"
            echo "      Delete a task"
            echo "  report [filter] [value]"
            echo "      Generate a report of tasks"
            echo "  help"
            echo "      Show this help message"
            ;;
        *)
            echo "Error: Unknown command '$command'."
            echo "Run '$0 help' for usage information."
            return 1
        ;;
esac 
}

# Call the main function with all arguments
main "$@" 