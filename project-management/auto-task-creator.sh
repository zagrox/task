#!/bin/bash

# ========================================================
# Auto Task Creator for MailZila
# ========================================================
#
# This script automatically creates tasks based on git commits
# and generates notes from agent results summaries
#
# Usage: ./auto-task-creator.sh [--commit-message "Message"] [--agent-summary "Summary text"] [--title "Task title"] [--type fix|feature|implement]
#
# If no arguments are provided, the script will analyze the most recent git commit

set -e

# Configuration
TASKS_FILE="$(dirname "$0")/tasks.json"
SCRIPT_DIR="$(dirname "$0")"
TASKS_ENDPOINT="http://localhost/tasks" # Change this to your application URL if needed
GIT_ROOT="$(git rev-parse --show-toplevel 2>/dev/null || echo ".")"
PHP_BIN=$(which php || echo "php")

# Function to check if jq is installed
check_requirements() {
    if ! command -v jq &> /dev/null; then
        echo "Error: jq is required but not installed."
        echo "Please install jq to continue:"
        echo "  - On macOS: brew install jq"
        echo "  - On Debian/Ubuntu: apt-get install jq"
        echo "  - On CentOS/RHEL: yum install jq"
        exit 1
    fi
    
    if ! command -v git &> /dev/null; then
        echo "Error: git is required but not installed."
        exit 1
    fi
}

# Function to check if we're in a git repository
check_git_repo() {
    if ! git rev-parse --is-inside-work-tree > /dev/null 2>&1; then
        echo "Error: Not inside a git repository."
        exit 1
    fi
}

# Function to extract task type from commit message
extract_task_type() {
    local commit_message="$1"
    
    if [[ $commit_message =~ ^[Ff]ix ]]; then
        echo "fix"
    elif [[ $commit_message =~ ^[Ff]eat ]]; then
        echo "feature"
    elif [[ $commit_message =~ ^[Ii]mplement ]]; then
        echo "implement"
    elif [[ $commit_message =~ ^[Rr]efactor ]]; then
        echo "refactor"
    elif [[ $commit_message =~ ^[Dd]ocs ]]; then
        echo "docs"
    elif [[ $commit_message =~ ^[Ss]tyle ]]; then
        echo "style"
    elif [[ $commit_message =~ ^[Tt]est ]]; then
        echo "test"
    else
        echo "task"
    fi
}

# Function to extract version from git tags
extract_version() {
    # Try to get the latest version tag
    local version=$(git describe --tags --abbrev=0 2>/dev/null | grep -E '^v?[0-9]+\.[0-9]+\.[0-9]+' || echo "")
    
    if [ -z "$version" ]; then
        # Check for version.json file
        if [ -f "$GIT_ROOT/version.json" ]; then
            version=$(jq -r '.version' "$GIT_ROOT/version.json" 2>/dev/null || echo "")
        fi
    fi
    
    # Remove leading 'v' if present
    version=${version#v}
    
    echo "$version"
}

# Function to determine related feature/module from changed files
extract_feature() {
    local changed_files="$1"
    
    # Default feature name
    local feature="general"
    
    # Extract base directories from changed files to determine modules/features
    local modules=$(echo "$changed_files" | grep -v '^$' | awk -F'/' '{print $1}' | sort | uniq)
    
    # If a primary module/directory was changed, use it as the feature
    if echo "$modules" | grep -q "^app$"; then
        feature="app"
        
        # Check if a specific module/controller was affected
        local app_components=$(echo "$changed_files" | grep "^app/" | awk -F'/' '{print $2}' | sort | uniq)
        
        if echo "$app_components" | grep -q "^Http$"; then
            local controllers=$(echo "$changed_files" | grep "^app/Http/Controllers" | awk -F'/' '{print $4}' | sed 's/Controller.php//' | sort | uniq)
            if [ ! -z "$controllers" ]; then
                feature=$(echo "$controllers" | head -1)
            fi
        elif [ ! -z "$app_components" ]; then
            feature=$(echo "$app_components" | head -1)
        fi
    elif echo "$modules" | grep -q "^resources$"; then
        feature="frontend"
        
        # Check if a specific view was affected
        local views=$(echo "$changed_files" | grep "^resources/views/" | awk -F'/' '{print $3}' | sort | uniq)
        if [ ! -z "$views" ]; then
            feature="view-$(echo "$views" | head -1)"
        fi
    elif [ ! -z "$modules" ]; then
        feature=$(echo "$modules" | head -1)
    fi
    
    echo "$feature"
}

# Function to determine priority based on commit message and changes
determine_priority() {
    local commit_message="$1"
    local type="$2"
    
    if [[ $commit_message =~ \[urgent\] ]] || [[ $commit_message =~ \[critical\] ]]; then
        echo "high"
    elif [[ $type == "fix" ]] && [[ $commit_message =~ \[important\] ]]; then
        echo "high"
    elif [[ $type == "fix" ]]; then
        echo "medium"
    else
        echo "low"
    fi
}

# Function to create a task from git commit
create_task_from_commit() {
    local commit_message="$1"
    local agent_summary="$2"
    local manual_title="$3"
    local manual_type="$4"
    
    # Extract commit info
    local commit_author=$(git log -1 --pretty=format:'%an')
    local commit_hash=$(git log -1 --pretty=format:'%h')
    local commit_date=$(git log -1 --pretty=format:'%ad' --date=iso)
    local changed_files=$(git log -1 --name-only --pretty=format:'')
    
    # Extract task properties
    local task_type=${manual_type:-$(extract_task_type "$commit_message")}
    local task_feature=$(extract_feature "$changed_files")
    local task_priority=$(determine_priority "$commit_message" "$task_type")
    local task_version=$(extract_version)
    
    # Generate task title if not provided
    local task_title="$manual_title"
    if [ -z "$task_title" ]; then
        # Clean up commit message for title (remove type prefix, etc.)
        task_title=$(echo "$commit_message" | sed -E 's/^(fix|feat|implement|refactor|docs|style|test)(\([^)]+\))?:\s*//i')
        task_title=$(echo "$task_title" | sed -E 's/\[urgent\]|\[critical\]|\[important\]//g' | xargs)
        
        # Capitalize first letter
        task_title="$(tr '[:lower:]' '[:upper:]' <<< ${task_title:0:1})${task_title:1}"
    fi
    
    # Generate task description
    local task_description="Automatically generated task from commit ${commit_hash}.\n\n"
    task_description+="Commit Message: ${commit_message}\n"
    task_description+="Type: ${task_type}\n"
    task_description+="Changed Files:\n"
    
    # Add list of changed files
    while IFS= read -r file; do
        if [ ! -z "$file" ]; then
            task_description+="- ${file}\n"
        fi
    done <<< "$changed_files"
    
    # Determine task status (always mark as completed since this is for completed work)
    local task_status="completed"
    
    # Determine if this is a user or AI task
    local task_assignee="user"
    if [[ $commit_message =~ \[ai\] ]] || [[ $commit_author =~ [Aa][Ii] ]]; then
        task_assignee="ai"
    fi
    
    # Prepare task creation data
    local task_data=$(cat <<EOF
{
  "title": "$(echo "$task_title" | sed 's/"/\\"/g')",
  "description": "$(echo "$task_description" | sed 's/"/\\"/g')",
  "assignee": "$task_assignee",
  "status": "$task_status",
  "priority": "$task_priority",
  "related_feature": "$task_feature",
  "related_phase": "implementation",
  "progress": 100,
  "tags": "$task_type,auto-created",
  "version": "$task_version"
}
EOF
)

    # Create the task directly by modifying the JSON file
    if [ -f "$TASKS_FILE" ]; then
        echo "Creating task in $TASKS_FILE..."
        
        # Create a backup first
        cp "$TASKS_FILE" "${TASKS_FILE}.bak"
        
        # Get the next ID
        local next_id=$(jq -r '.next_id // 1' "$TASKS_FILE")
        
        # Add the task to the tasks array
        local now_iso=$(date -u +"%Y-%m-%dT%H:%M:%SZ")
        local task_json=$(cat <<EOF
{
  "id": $next_id,
  "title": "$(echo "$task_title" | sed 's/"/\\"/g')",
  "description": "$(echo "$task_description" | sed 's/"/\\"/g')",
  "assignee": "$task_assignee",
  "status": "$task_status",
  "priority": "$task_priority",
  "created_at": "$now_iso",
  "updated_at": "$now_iso",
  "due_date": null,
  "related_feature": "$task_feature",
  "related_phase": "implementation",
  "dependencies": [],
  "progress": 100,
  "notes": [],
  "tags": "$task_type,auto-created",
  "estimated_hours": 0,
  "actual_hours": 0,
  "version": "$task_version"
}
EOF
)
        
        # Add task to the file
        jq --argjson task "$task_json" --arg next_id "$(($next_id + 1))" '
          .tasks += [$task] | 
          .next_id = ($next_id | tonumber) | 
          .metadata.total_tasks = (.tasks | length) | 
          .metadata.completed_tasks = ([.tasks[] | select(.status == "completed")] | length) |
          .metadata.user_tasks = ([.tasks[] | select(.assignee == "user")] | length) |
          .metadata.ai_tasks = ([.tasks[] | select(.assignee == "ai")] | length) |
          .metadata.last_updated = (now | todate)
        ' "$TASKS_FILE" > "${TASKS_FILE}.tmp" && mv "${TASKS_FILE}.tmp" "$TASKS_FILE"
        
        echo "✅ Task #$next_id created successfully!"
        
        # Add agent summary as note if provided
        if [ ! -z "$agent_summary" ]; then
            add_note_to_task "$next_id" "$agent_summary"
        fi
        
        return "$next_id"
    else
        echo "Error: Tasks file not found at $TASKS_FILE"
        echo "Please make sure the tasks system is properly initialized."
        exit 1
    fi
}

# Function to add a note to an existing task
add_note_to_task() {
    local task_id="$1"
    local note_content="$2"
    
    if [ -z "$task_id" ] || [ -z "$note_content" ]; then
        echo "Error: Task ID and note content are required"
        return 1
    fi
    
    if [ -f "$TASKS_FILE" ]; then
        # Create a backup first
        cp "$TASKS_FILE" "${TASKS_FILE}.bak"
        
        # Find the task in the file
        local task_exists=$(jq ".tasks[] | select(.id == $task_id) | .id" "$TASKS_FILE")
        
        if [ -z "$task_exists" ]; then
            echo "Error: Task #$task_id not found"
            return 1
        fi
        
        # Add the note to the task
        local now_iso=$(date -u +"%Y-%m-%dT%H:%M:%SZ")
        local note_json=$(cat <<EOF
{
  "content": "$(echo "$note_content" | sed 's/"/\\"/g')",
  "timestamp": "$now_iso"
}
EOF
)
        
        # Update task in the file
        jq --argjson note "$note_json" --arg task_id "$task_id" --arg now "$now_iso" '
          .tasks = (.tasks | map(
            if .id == ($task_id | tonumber) then 
              .notes += [$note] | 
              .updated_at = $now
            else 
              .
            end
          )) |
          .metadata.last_updated = $now
        ' "$TASKS_FILE" > "${TASKS_FILE}.tmp" && mv "${TASKS_FILE}.tmp" "$TASKS_FILE"
        
        echo "✅ Note added to Task #$task_id"
        return 0
    else
        echo "Error: Tasks file not found at $TASKS_FILE"
        return 1
    fi
}

# Main function
main() {
    check_requirements
    
    # Process arguments
    local commit_message=""
    local agent_summary=""
    local task_title=""
    local task_type=""
    
    while [[ $# -gt 0 ]]; do
        case "$1" in
            --commit-message)
                commit_message="$2"
                shift 2
                ;;
            --agent-summary)
                agent_summary="$2"
                shift 2
                ;;
            --title)
                task_title="$2"
                shift 2
                ;;
            --type)
                task_type="$2"
                shift 2
                ;;
            *)
                echo "Unknown option: $1"
                exit 1
                ;;
        esac
    done
    
    # Check if we're in a git repo
    check_git_repo
    
    # If no commit message provided, use the last commit
    if [ -z "$commit_message" ]; then
        commit_message=$(git log -1 --pretty=format:'%s')
        
        if [ -z "$commit_message" ]; then
            echo "Error: No commit message found and none provided."
            exit 1
        fi
        
        echo "Using last commit message: $commit_message"
    fi
    
    # Create task from the commit
    local task_id=""
    create_task_from_commit "$commit_message" "$agent_summary" "$task_title" "$task_type"
    task_id=$?
    
    if [ $task_id -gt 0 ]; then
        echo "Task #$task_id has been created. You can view it at:"
        echo "$TASKS_ENDPOINT/$task_id"
    fi
}

# Run the main function with all arguments
main "$@" 