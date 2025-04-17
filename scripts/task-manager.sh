#!/bin/bash
# scripts/task-manager.sh
# Purpose: Manage project tasks for both users and AI

# Configuration
TASKS_DIR="project-management"
TASKS_FILE="${TASKS_DIR}/tasks.json"
VERSION="1.0.0"

# Check dependencies
if ! command -v jq &> /dev/null; then
  echo "Error: jq is required but not installed."
  echo "Install with: brew install jq"
  exit 1
fi

# Ensure tasks directory exists
if [ ! -d "$TASKS_DIR" ]; then
  mkdir -p "$TASKS_DIR"
  echo "Created tasks directory: $TASKS_DIR"
fi

# Ensure tasks file exists
if [ ! -f "$TASKS_FILE" ]; then
  echo "Creating initial tasks file..."
  echo '{
    "tasks": [],
    "metadata": {
      "last_updated": "'"$(date -u +"%Y-%m-%dT%H:%M:%SZ")"'",
      "total_tasks": 0,
      "completed_tasks": 0,
      "user_tasks": 0,
      "ai_tasks": 0
    },
    "next_id": "T1"
  }' > "$TASKS_FILE"
  echo "Initialized tasks file: $TASKS_FILE"
fi

# Update metadata function
function update_metadata() {
  local total=$(jq '.tasks | length' "$TASKS_FILE")
  local completed=$(jq '.tasks[] | select(.status == "completed") | length' "$TASKS_FILE")
  local user_tasks=$(jq '.tasks[] | select(.assigned_to != "ai-assistant") | length' "$TASKS_FILE")
  local ai_tasks=$(jq '.tasks[] | select(.assigned_to == "ai-assistant") | length' "$TASKS_FILE")
  
  jq --arg date "$(date -u +"%Y-%m-%dT%H:%M:%SZ")" \
     --arg total "$total" \
     --arg completed "$completed" \
     --arg user "$user_tasks" \
     --arg ai "$ai_tasks" \
     '.metadata.last_updated = $date | 
      .metadata.total_tasks = ($total|tonumber) | 
      .metadata.completed_tasks = ($completed|tonumber) | 
      .metadata.user_tasks = ($user|tonumber) | 
      .metadata.ai_tasks = ($ai|tonumber)' "$TASKS_FILE" > "$TASKS_FILE.tmp" && 
  mv "$TASKS_FILE.tmp" "$TASKS_FILE"
}

# Helper function to generate next ID
function get_next_id() {
  local next_id=$(jq -r '.next_id' "$TASKS_FILE")
  local id_number="${next_id:1}"
  local new_number=$((id_number + 1))
  local new_id="T$new_number"
  
  jq --arg new_id "$new_id" '.next_id = $new_id' "$TASKS_FILE" > "$TASKS_FILE.tmp" && 
  mv "$TASKS_FILE.tmp" "$TASKS_FILE"
  
  echo "$next_id"
}

# Add task function
function add_task() {
  local title=""
  local description=""
  local status="pending"
  local priority="medium"
  local assigned=""
  local estimated=""
  local due=""
  local feature=""
  local phase=""
  local tags=""
  
  # Parse named parameters
  while [[ $# -gt 0 ]]; do
    key="$1"
    case $key in
      --title)
        title="$2"
        shift 2
        ;;
      --desc)
        description="$2"
        shift 2
        ;;
      --status)
        status="$2"
        shift 2
        ;;
      --priority)
        priority="$2"
        shift 2
        ;;
      --assigned)
        assigned="$2"
        shift 2
        ;;
      --estimated)
        estimated="$2"
        shift 2
        ;;
      --due)
        due="$2"
        shift 2
        ;;
      --feature)
        feature="$2"
        shift 2
        ;;
      --phase)
        phase="$2"
        shift 2
        ;;
      --tags)
        tags="$2"
        shift 2
        ;;
      *)
        echo "Unknown parameter: $1"
        exit 1
        ;;
    esac
  done
  
  # Validate required fields
  if [ -z "$title" ]; then
    echo "Error: Task title is required"
    echo "Usage: $0 add --title \"Task title\" [options]"
    exit 1
  fi
  
  # Create task ID
  local id=$(get_next_id)
  
  # Format tags as JSON array
  local tags_json="[]"
  if [ -n "$tags" ]; then
    tags_json=$(echo "$tags" | sed 's/,/","/g' | sed 's/^/["/g' | sed 's/$/"]/')
  fi
  
  # Format dates
  local created_at=$(date -u +"%Y-%m-%dT%H:%M:%SZ")
  local due_formatted=""
  if [ -n "$due" ]; then
    # Convert YYYY-MM-DD to ISO format with end of day
    due_formatted="${due}T23:59:59Z"
  fi
  
  # Add the task to the JSON file
  jq --arg id "$id" \
     --arg title "$title" \
     --arg desc "$description" \
     --arg status "$status" \
     --arg priority "$priority" \
     --arg created_by "$(whoami)" \
     --arg assigned "${assigned:-unassigned}" \
     --arg created_at "$created_at" \
     --arg due_date "$due_formatted" \
     --argjson estimated "$([ -n "$estimated" ] && echo "$estimated" || echo "null")" \
     --arg feature "$feature" \
     --arg phase "$phase" \
     --argjson tags "$tags_json" \
     '.tasks += [{
       "id": $id,
       "title": $title,
       "description": $desc,
       "status": $status,
       "priority": $priority,
       "created_by": $created_by,
       "assigned_to": $assigned,
       "created_at": $created_at,
       "due_date": $due_date,
       "estimated_hours": $estimated,
       "logged_hours": 0,
       "progress": 0,
       "feature": $feature,
       "phase": $phase,
       "tags": $tags,
       "notes": [],
       "history": []
     }]' "$TASKS_FILE" > "$TASKS_FILE.tmp" && 
  mv "$TASKS_FILE.tmp" "$TASKS_FILE"
  
  update_metadata
  
  echo "Task $id added: $title"
}

# Update task function
function update_task() {
  local task_id=$1
  shift
  
  # Check if task exists
  if ! jq -e --arg id "$task_id" '.tasks[] | select(.id == $id)' "$TASKS_FILE" >/dev/null; then
    echo "Error: Task $task_id not found"
    exit 1
  fi
  
  # Initialize fields to update
  local update_fields=""
  local history_updates=""
  
  # Parse named parameters
  while [[ $# -gt 0 ]]; do
    key="$1"
    case $key in
      --title)
        local old_value=$(jq -r --arg id "$task_id" '.tasks[] | select(.id == $id) | .title' "$TASKS_FILE")
        update_fields+=" | .title = \"$2\""
        history_updates+=", {\"field\": \"title\", \"from\": \"$old_value\", \"to\": \"$2\", \"by\": \"$(whoami)\", \"timestamp\": \"$(date -u +"%Y-%m-%dT%H:%M:%SZ")\"}"
        shift 2
        ;;
      --desc)
        update_fields+=" | .description = \"$2\""
        shift 2
        ;;
      --status)
        local old_value=$(jq -r --arg id "$task_id" '.tasks[] | select(.id == $id) | .status' "$TASKS_FILE")
        update_fields+=" | .status = \"$2\""
        history_updates+=", {\"field\": \"status\", \"from\": \"$old_value\", \"to\": \"$2\", \"by\": \"$(whoami)\", \"timestamp\": \"$(date -u +"%Y-%m-%dT%H:%M:%SZ")\"}"
        shift 2
        ;;
      --priority)
        local old_value=$(jq -r --arg id "$task_id" '.tasks[] | select(.id == $id) | .priority' "$TASKS_FILE")
        update_fields+=" | .priority = \"$2\""
        history_updates+=", {\"field\": \"priority\", \"from\": \"$old_value\", \"to\": \"$2\", \"by\": \"$(whoami)\", \"timestamp\": \"$(date -u +"%Y-%m-%dT%H:%M:%SZ")\"}"
        shift 2
        ;;
      --assigned)
        local old_value=$(jq -r --arg id "$task_id" '.tasks[] | select(.id == $id) | .assigned_to' "$TASKS_FILE")
        update_fields+=" | .assigned_to = \"$2\""
        history_updates+=", {\"field\": \"assigned_to\", \"from\": \"$old_value\", \"to\": \"$2\", \"by\": \"$(whoami)\", \"timestamp\": \"$(date -u +"%Y-%m-%dT%H:%M:%SZ")\"}"
        shift 2
        ;;
      --progress)
        local old_value=$(jq -r --arg id "$task_id" '.tasks[] | select(.id == $id) | .progress' "$TASKS_FILE")
        update_fields+=" | .progress = $2"
        history_updates+=", {\"field\": \"progress\", \"from\": \"$old_value\", \"to\": \"$2\", \"by\": \"$(whoami)\", \"timestamp\": \"$(date -u +"%Y-%m-%dT%H:%M:%SZ")\"}"
        shift 2
        ;;
      --logged)
        local old_value=$(jq -r --arg id "$task_id" '.tasks[] | select(.id == $id) | .logged_hours' "$TASKS_FILE")
        update_fields+=" | .logged_hours = (.logged_hours + $2)"
        history_updates+=", {\"field\": \"logged_hours\", \"from\": \"$old_value\", \"to\": \"$(($old_value + $2))\", \"by\": \"$(whoami)\", \"timestamp\": \"$(date -u +"%Y-%m-%dT%H:%M:%SZ")\"}"
        shift 2
        ;;
      --due)
        local old_value=$(jq -r --arg id "$task_id" '.tasks[] | select(.id == $id) | .due_date' "$TASKS_FILE")
        update_fields+=" | .due_date = \"${2}T23:59:59Z\""
        history_updates+=", {\"field\": \"due_date\", \"from\": \"$old_value\", \"to\": \"${2}T23:59:59Z\", \"by\": \"$(whoami)\", \"timestamp\": \"$(date -u +"%Y-%m-%dT%H:%M:%SZ")\"}"
        shift 2
        ;;
      *)
        echo "Unknown parameter: $1"
        exit 1
        ;;
    esac
  done
  
  # Apply updates
  if [ -n "$update_fields" ]; then
    history_command=""
    if [ -n "$history_updates" ]; then
      # Remove the leading comma
      history_updates="${history_updates:2}"
      history_command=" | .history += [$history_updates]"
    fi
    
    jq --arg id "$task_id" \
       ".tasks = (.tasks | map(if .id == \$id then . $update_fields $history_command else . end))" \
       "$TASKS_FILE" > "$TASKS_FILE.tmp" && 
    mv "$TASKS_FILE.tmp" "$TASKS_FILE"
    
    update_metadata
    echo "Task $task_id updated"
  else
    echo "No updates specified"
  fi
}

# Add note to task
function add_note() {
  local task_id=$1
  local note_text=$2
  
  # Check if task exists
  if ! jq -e --arg id "$task_id" '.tasks[] | select(.id == $id)' "$TASKS_FILE" >/dev/null; then
    echo "Error: Task $task_id not found"
    exit 1
  fi
  
  # Add the note
  jq --arg id "$task_id" \
     --arg author "$(whoami)" \
     --arg content "$note_text" \
     --arg timestamp "$(date -u +"%Y-%m-%dT%H:%M:%SZ")" \
     '.tasks = (.tasks | map(if .id == $id then .notes += [{"author": $author, "content": $content, "timestamp": $timestamp}] else . end))' \
     "$TASKS_FILE" > "$TASKS_FILE.tmp" && 
  mv "$TASKS_FILE.tmp" "$TASKS_FILE"
  
  echo "Note added to task $task_id"
}

# List tasks function
function list_tasks() {
  local filter_command="."
  local sort_field="id"
  
  # Parse parameters for filtering and sorting
  while [[ $# -gt 0 ]]; do
    key="$1"
    case $key in
      --status)
        filter_command="$filter_command | select(.status == \"$2\")"
        shift 2
        ;;
      --assigned)
        filter_command="$filter_command | select(.assigned_to == \"$2\")"
        shift 2
        ;;
      --feature)
        filter_command="$filter_command | select(.feature == \"$2\")"
        shift 2
        ;;
      --phase)
        filter_command="$filter_command | select(.phase == \"$2\")"
        shift 2
        ;;
      --tag)
        filter_command="$filter_command | select(.tags | contains([\"$2\"]))"
        shift 2
        ;;
      --sort)
        sort_field=$2
        shift 2
        ;;
      *)
        echo "Unknown parameter: $1"
        exit 1
        ;;
    esac
  done
  
  # List tasks with the applied filters
  echo "Task List:"
  echo "---------------------------------------------------------------------------------------"
  printf "%-6s %-30s %-12s %-10s %-12s %-8s\n" "ID" "Title" "Status" "Priority" "Assigned" "Progress"
  echo "---------------------------------------------------------------------------------------"
  
  jq -r --arg filter "$filter_command" --arg sort "$sort_field" \
     ".tasks[] | $filter_command | [.id, .title, .status, .priority, .assigned_to, .progress] | \"%s %-30s %-12s %-10s %-12s %3s%%\"" \
     "$TASKS_FILE" | sort -k1,1
  
  echo "---------------------------------------------------------------------------------------"
  echo "Total: $(jq ".tasks[] | $filter_command" "$TASKS_FILE" | grep -c "id")"
}

# Generate report function
function generate_report() {
  local report_type=$1
  
  case $report_type in
    progress)
      echo "Progress Report by Feature:"
      echo "-----------------------------------------------------------"
      jq -r '.tasks | group_by(.feature) | map({feature: .[0].feature, tasks: length, completed: map(select(.status == "completed")) | length, progress: (map(.progress) | add / length | . * 100 | round / 100)}) | sort_by(.feature) | .[] | "Feature: \(.feature) - Tasks: \(.tasks), Completed: \(.completed), Avg Progress: \(.progress)%"' "$TASKS_FILE"
      ;;
    workload)
      echo "Workload Report by Assignee:"
      echo "-----------------------------------------------------------"
      jq -r '.tasks | group_by(.assigned_to) | map({assignee: .[0].assigned_to, tasks: length, pending: map(select(.status == "pending")) | length, in_progress: map(select(.status == "in-progress")) | length, completed: map(select(.status == "completed")) | length}) | sort_by(.assignee) | .[] | "Assignee: \(.assignee) - Total: \(.tasks), Pending: \(.pending), In Progress: \(.in_progress), Completed: \(.completed)"' "$TASKS_FILE"
      ;;
    upcoming)
      echo "Upcoming Tasks (Due in the next 7 days):"
      echo "-----------------------------------------------------------"
      local today=$(date -u +"%Y-%m-%dT%H:%M:%SZ")
      local in7days=$(date -u -v+7d +"%Y-%m-%dT%H:%M:%SZ" 2>/dev/null || date -u -d "+7 days" +"%Y-%m-%dT%H:%M:%SZ")
      jq -r --arg today "$today" --arg in7days "$in7days" \
         '.tasks[] | select(.status != "completed" and .due_date != null and .due_date >= $today and .due_date <= $in7days) | [.id, .title, .due_date[0:10], .assigned_to, .priority] | "%s %-30s Due: %s | Assigned: %-10s | Priority: %s"' \
         "$TASKS_FILE" | sort -k3,3
      ;;
    *)
      echo "Unknown report type: $report_type"
      echo "Available reports: progress, workload, upcoming"
      exit 1
      ;;
  esac
}

# Display help
function show_help() {
  echo "MailZila Task Manager v$VERSION"
  echo "Usage: $0 <command> [options]"
  echo ""
  echo "Commands:"
  echo "  add                Add a new task"
  echo "    --title          Task title (required)"
  echo "    --desc           Task description"
  echo "    --status         Task status (default: pending)"
  echo "    --priority       Task priority (default: medium)"
  echo "    --assigned       Assignee username"
  echo "    --estimated      Estimated hours"
  echo "    --due            Due date (YYYY-MM-DD)"
  echo "    --feature        Related feature"
  echo "    --phase          Related project phase"
  echo "    --tags           Comma-separated tags"
  echo ""
  echo "  update <id>        Update a task"
  echo "    --title          New title"
  echo "    --desc           New description"
  echo "    --status         New status"
  echo "    --priority       New priority"
  echo "    --assigned       New assignee"
  echo "    --progress       Progress percentage (0-100)"
  echo "    --logged         Hours to add to logged time"
  echo "    --due            New due date (YYYY-MM-DD)"
  echo ""
  echo "  note <id> <text>   Add a note to a task"
  echo ""
  echo "  list               List tasks"
  echo "    --status         Filter by status"
  echo "    --assigned       Filter by assignee"
  echo "    --feature        Filter by feature"
  echo "    --phase          Filter by project phase"
  echo "    --tag            Filter by tag"
  echo "    --sort           Sort by field (default: id)"
  echo ""
  echo "  report <type>      Generate a report"
  echo "    progress         Progress by feature"
  echo "    workload         Tasks by assignee"
  echo "    upcoming         Tasks due soon"
  echo ""
  echo "  help               Show this help message"
}

# Main function
function main() {
  COMMAND="${1:-""}"
  shift || true
  
  case "$COMMAND" in
    "add")
      add_task "$@"
      ;;
    "update")
      if [ -z "$2" ]; then
        echo "Error: Task ID is required"
        echo "Usage: $0 update <id> [options]"
        exit 1
      fi
      task_id=$2
      shift 2
      update_task "$task_id" "$@"
      ;;
    "note")
      if [ -z "$2" ] || [ -z "$3" ]; then
        echo "Error: Task ID and note text are required"
        echo "Usage: $0 note <id> <text>"
        exit 1
      fi
      add_note "$2" "$3"
      ;;
    "list")
      shift
      list_tasks "$@"
      ;;
    "report")
      if [ -z "$2" ]; then
        echo "Error: Report type is required"
        echo "Usage: $0 report <type>"
        exit 1
      fi
      generate_report "$2"
      ;;
    "help"|"--help|-h)
      show_help
      ;;
    *)
      echo "Unknown command: $1"
      echo "Run '$0 help' for usage information"
      exit 1
      ;;
  esac
  
  return $?
}

# Run main function
main "$@"
exit $? 