#!/bin/bash

# MailZila Task Management System
# A lightweight JSON-based task management system for MailZila project

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
    
    # Update the field based on its type
    local jq_filter=""
    local now=$(date -u +"%Y-%m-%dT%H:%M:%SZ")
    
    case "$field" in
        title|description|status|priority|assignee|due_date|related_feature|related_phase)
            jq_filter=".tasks = (.tasks | map(if .id == $id then .${field} = \"$value\" | .updated_at = \"$now\" else . end))"
            ;;
        progress|estimated_hours|actual_hours)
            # Verify it's a number
            if ! [[ "$value" =~ ^[0-9]+(\.[0-9]+)?$ ]]; then
                echo "Error: $field must be a number."
                return 1
            fi
            jq_filter=".tasks = (.tasks | map(if .id == $id then .${field} = ($value | tonumber) | .updated_at = \"$now\" else . end))"
            ;;
        tags)
            # Convert comma-separated tags to JSON array
            local tags_json=$(echo "$value" | jq -R 'split(",") | map(. | gsub("^\\s+|\\s+$"; ""))')
            jq_filter=".tasks = (.tasks | map(if .id == $id then .tags = $tags_json | .updated_at = \"$now\" else . end))"
            ;;
        dependencies)
            # Convert comma-separated dependencies to JSON array of numbers
            local deps_array=$(echo "$value" | tr ',' ' ' | xargs -n 1 | jq -R '. | tonumber' | jq -s '.')
            jq_filter=".tasks = (.tasks | map(if .id == $id then .dependencies = $deps_array | .updated_at = \"$now\" else . end))"
            ;;
        *)
            echo "Error: Unknown field '$field'."
            echo "Available fields: title, description, status, priority, assignee, progress, due_date, related_feature, related_phase, tags, dependencies, estimated_hours, actual_hours"
            return 1
            ;;
    esac
    
    # Apply the update
    jq "$jq_filter" "$TASKS_FILE" > "$TASKS_FILE.tmp" && 
    mv "$TASKS_FILE.tmp" "$TASKS_FILE"
    
    # Update metadata if status was changed
    if [ "$field" = "status" ] || [ "$field" = "assignee" ]; then
        update_metadata
    fi
    
    echo "Task #$id updated: $field set to $value"
}

# Add a note to a task
add_note() {
    local id="$1"
    local note_content="$2"
    
    if [ -z "$id" ] || [ -z "$note_content" ]; then
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
    jq --arg note "$note_content" \
       --arg timestamp "$now" \
       --arg now "$now" \
       --argjson id "$id" \
       '.tasks = (.tasks | map(if .id == $id then 
           .notes += [{
               "content": $note,
               "timestamp": $timestamp
           }] | 
           .updated_at = $now 
       else . end))' "$TASKS_FILE" > "$TASKS_FILE.tmp" && 
    mv "$TASKS_FILE.tmp" "$TASKS_FILE"
    
    echo "Note added to Task #$id"
}

# Delete a task
delete_task() {
    local id="$1"
    
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
    
    # Create backup before modifying
    create_backup
    
    # Remove the task
    jq --argjson id "$id" '.tasks = (.tasks | map(select(.id != $id)))' "$TASKS_FILE" > "$TASKS_FILE.tmp" && 
    mv "$TASKS_FILE.tmp" "$TASKS_FILE"
    
    # Update metadata
    update_metadata
    
    echo "Task #$id deleted."
}

# Generate a summary report
generate_report() {
    local report_type="${1:-summary}"
    
    case "$report_type" in
        summary)
            local total=$(jq '.tasks | length' "$TASKS_FILE")
            local completed=$(jq '.tasks[] | select(.status == "completed") | .id' "$TASKS_FILE" | wc -l)
            local in_progress=$(jq '.tasks[] | select(.status == "in-progress") | .id' "$TASKS_FILE" | wc -l)
            local pending=$(jq '.tasks[] | select(.status == "pending") | .id' "$TASKS_FILE" | wc -l)
            local user_tasks=$(jq '.tasks[] | select(.assignee == "user") | .id' "$TASKS_FILE" | wc -l)
            local ai_tasks=$(jq '.tasks[] | select(.assignee == "ai") | .id' "$TASKS_FILE" | wc -l)
            
            echo "========== Task Summary Report =========="
            echo "Total Tasks: $total"
            echo "Completed: $completed"
            echo "In Progress: $in_progress"
            echo "Pending: $pending"
            echo "Assigned to User: $user_tasks"
            echo "Assigned to AI: $ai_tasks"
            echo "========================================"
            ;;
        
        progress)
            echo "========== Progress Report =========="
            jq -r '.tasks | group_by(.status) | map({status: .[0].status, count: length}) | .[] | "\(.status): \(.count) task(s)"' "$TASKS_FILE"
            
            # Calculate average progress
            local avg_progress=$(jq -r '.tasks | [.[] | .progress] | add / length' "$TASKS_FILE")
            echo "Average Progress: $(printf "%.1f" "$avg_progress")%"
            echo "====================================="
            ;;
        
        feature)
            echo "========== Feature Report =========="
            jq -r '.tasks | group_by(.related_feature) | map({feature: (if .[0].related_feature == "" or .[0].related_feature == null then "Unassigned" else .[0].related_feature end), count: length}) | .[] | "\(.feature): \(.count) task(s)"' "$TASKS_FILE"
            echo "====================================="
            ;;
        
        phase)
            echo "========== Phase Report =========="
            jq -r '.tasks | group_by(.related_phase) | map({phase: (if .[0].related_phase == "" or .[0].related_phase == null then "Unassigned" else .[0].related_phase end), count: length}) | .[] | "\(.phase): \(.count) task(s)"' "$TASKS_FILE"
            echo "====================================="
            ;;
        
        due)
            echo "========== Due Date Report =========="
            local today=$(date +"%Y-%m-%d")
            local overdue=$(jq --arg today "$today" '.tasks[] | select(.due_date != null and .due_date != "" and .due_date < $today and .status != "completed") | .id' "$TASKS_FILE" | wc -l)
            
            echo "Overdue Tasks: $overdue"
            
            echo "Tasks Due Today:"
            jq -r --arg today "$today" '.tasks[] | select(.due_date == $today) | "#\(.id): \(.title) [\(.status)]"' "$TASKS_FILE" || echo "None"
            
            echo "Upcoming Due Dates (Next 7 Days):"
            for i in {1..7}; do
                local future_date=$(date -v+${i}d +"%Y-%m-%d")
                local due_tasks=$(jq -r --arg date "$future_date" '.tasks[] | select(.due_date == $date) | "#\(.id): \(.title) [\(.status)]"' "$TASKS_FILE")
                if [ ! -z "$due_tasks" ]; then
                    echo "Due on $future_date:"
                    echo "$due_tasks"
                fi
            done
            echo "====================================="
            ;;
        
        time)
            echo "========== Time Tracking Report =========="
            local total_est=$(jq -r '.tasks | [.[] | .estimated_hours] | add' "$TASKS_FILE")
            local total_actual=$(jq -r '.tasks | [.[] | .actual_hours] | add' "$TASKS_FILE")
            
            echo "Total Estimated Hours: $(printf "%.1f" "$total_est")"
            echo "Total Actual Hours: $(printf "%.1f" "$total_actual")"
            
            if (( $(echo "$total_est > 0" | bc -l) )); then
                local ratio=$(echo "scale=2; $total_actual / $total_est" | bc -l)
                echo "Time Utilization Ratio: $ratio"
            fi
            
            echo "Time per Feature:"
            jq -r '.tasks | group_by(.related_feature) | map({feature: (if .[0].related_feature == "" or .[0].related_feature == null then "Unassigned" else .[0].related_feature end), estimated: [.[] | .estimated_hours] | add, actual: [.[] | .actual_hours] | add}) | .[] | "\(.feature): Estimated \(.estimated)h, Actual \(.actual)h"' "$TASKS_FILE"
            echo "======================================="
            ;;
        
        *)
            echo "Error: Unknown report type '$report_type'."
            echo "Available report types: summary, progress, feature, phase, due, time"
            return 1
            ;;
    esac
}

# Show dependencies graph
show_dependencies() {
    local id="$1"
    
    if [ -z "$id" ]; then
        echo "Error: Task ID is required."
        echo "Usage: $0 dependencies <task_id>"
        return 1
    fi
    
    # Check if the task exists
    if ! jq -e ".tasks[] | select(.id == $id)" "$TASKS_FILE" > /dev/null; then
        echo "Error: Task #$id not found."
        return 1
    fi
    
    echo "Dependencies for Task #$id:"
    
    # Show what this task depends on
    local depends_on=$(jq -r ".tasks[] | select(.id == $id) | .dependencies[]?" "$TASKS_FILE")
    if [ ! -z "$depends_on" ]; then
        echo "This task depends on:"
        for dep_id in $depends_on; do
            local dep_title=$(jq -r ".tasks[] | select(.id == $dep_id) | .title" "$TASKS_FILE")
            local dep_status=$(jq -r ".tasks[] | select(.id == $dep_id) | .status" "$TASKS_FILE")
            echo "  #$dep_id: $dep_title [$dep_status]"
        done
    else
        echo "This task has no dependencies."
    fi
    
    # Show tasks that depend on this task
    local dependents=$(jq -r ".tasks[] | select(.dependencies | index($id) != null) | .id" "$TASKS_FILE")
    if [ ! -z "$dependents" ]; then
        echo "Tasks that depend on this task:"
        for dep_id in $dependents; do
            local dep_title=$(jq -r ".tasks[] | select(.id == $dep_id) | .title" "$TASKS_FILE")
            local dep_status=$(jq -r ".tasks[] | select(.id == $dep_id) | .status" "$TASKS_FILE")
            echo "  #$dep_id: $dep_title [$dep_status]"
        done
    else
        echo "No tasks depend on this task."
    fi
}

# Export tasks to other formats
export_tasks() {
    local format="${1:-json}"
    local output_file="$2"
    
    if [ -z "$output_file" ]; then
        output_file="$(dirname "$TASKS_FILE")/exported_tasks_$(date +"%Y%m%d").${format}"
    fi
    
    case "$format" in
        json)
            cp "$TASKS_FILE" "$output_file"
            ;;
        csv)
            echo "id,title,description,assignee,status,priority,created_at,updated_at,due_date,related_feature,related_phase,progress,estimated_hours,actual_hours" > "$output_file"
            jq -r '.tasks[] | [.id, .title, .description, .assignee, .status, .priority, .created_at, .updated_at, .due_date, .related_feature, .related_phase, .progress, .estimated_hours, .actual_hours] | @csv' "$TASKS_FILE" >> "$output_file"
            ;;
        md|markdown)
            echo "# MailZila Task Management Export" > "$output_file"
            echo "Generated on: $(date)" >> "$output_file"
            echo "" >> "$output_file"
            
            # Add summary
            echo "## Summary" >> "$output_file"
            local total=$(jq '.tasks | length' "$TASKS_FILE")
            local completed=$(jq '.tasks[] | select(.status == "completed") | .id' "$TASKS_FILE" | wc -l)
            echo "- Total Tasks: $total" >> "$output_file"
            echo "- Completed Tasks: $completed" >> "$output_file"
            echo "- Completion Rate: $(( (completed * 100) / (total > 0 ? total : 1) ))%" >> "$output_file"
            echo "" >> "$output_file"
            
            # Add task details
            echo "## Tasks" >> "$output_file"
            jq -r '.tasks[] | "### #\(.id): \(.title)\n\n**Status:** \(.status)  \n**Priority:** \(.priority)  \n**Assignee:** \(.assignee)  \n**Progress:** \(.progress)%  \n**Due Date:** \(.due_date // \"Not set\")  \n\n\(.description)\n\n**Created:** \(.created_at)  \n**Last Updated:** \(.updated_at)  \n**Feature:** \(.related_feature // \"Not set\")  \n**Phase:** \(.related_phase // \"Not set\")  \n**Estimated Hours:** \(.estimated_hours)  \n**Actual Hours:** \(.actual_hours)  \n\n**Tags:** \(.tags | join(\", \"))  \n\n**Dependencies:** \(.dependencies | join(\", \") // \"None\")\n\n#### Notes:\n\(.notes | if length > 0 then map("- [\(.timestamp)] \(.content)") | join("\n") else "No notes." end)\n\n---\n"' "$TASKS_FILE" >> "$output_file"
            ;;
        *)
            echo "Error: Unsupported export format '$format'."
            echo "Available formats: json, csv, md, markdown"
            return 1
            ;;
    esac
    
    echo "Tasks exported to $output_file"
}

# Display help
show_help() {
    echo "MailZila Task Management System"
    echo "Usage: $0 <command> [arguments]"
    echo ""
    echo "Commands:"
    echo "  add <title> <description> [assignee] [priority] [status] [due_date] [feature] [phase] [tags] [estimated_hours]"
    echo "      Add a new task"
    echo ""
    echo "  list [filter] [value]"
    echo "      List all tasks or filter by: status, assignee, priority, feature, phase, tag, due, id"
    echo ""
    echo "  show <task_id>"
    echo "      Show detailed information about a specific task"
    echo ""
    echo "  update <task_id> <field> <value>"
    echo "      Update a specific field of a task"
    echo "      Fields: title, description, status, priority, assignee, progress, due_date,"
    echo "              related_feature, related_phase, tags, dependencies, estimated_hours, actual_hours"
    echo ""
    echo "  note <task_id> <note_content>"
    echo "      Add a note to a task"
    echo ""
    echo "  delete <task_id>"
    echo "      Delete a task"
    echo ""
    echo "  report [type]"
    echo "      Generate a report. Types: summary, progress, feature, phase, due, time"
    echo ""
    echo "  dependencies <task_id>"
    echo "      Show dependencies for a task"
    echo ""
    echo "  export [format] [output_file]"
    echo "      Export tasks to another format: json, csv, md, markdown"
    echo ""
    echo "  help"
    echo "      Display this help information"
    echo ""
    echo "Examples:"
    echo "  $0 add \"Create login page\" \"Design and implement the user login page\" user high pending \"2023-12-31\" frontend design \"ui,authentication\" 8"
    echo "  $0 list status in-progress"
    echo "  $0 update 1 status completed"
    echo "  $0 note 1 \"Added form validation\""
    echo "  $0 report due"
}

# Main command processing
case "$1" in
    add)
        shift
        add_task "$@"
        ;;
    list)
        shift
        list_tasks "$@"
        ;;
    show)
        shift
        show_task "$@"
        ;;
    update)
        shift
        update_task "$@"
        ;;
    note)
        shift
        add_note "$@"
        ;;
    delete)
        shift
        delete_task "$@"
        ;;
    report)
        shift
        generate_report "$@"
        ;;
    dependencies)
        shift
        show_dependencies "$@"
        ;;
    export)
        shift
        export_tasks "$@"
        ;;
    help|--help|-h)
        show_help
        ;;
    *)
        if [ -z "$1" ]; then
            show_help
        else
            echo "Error: Unknown command '$1'."
            echo "Run '$0 help' for usage information."
            exit 1
        fi
        ;;
esac 