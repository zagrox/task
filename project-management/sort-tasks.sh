#!/bin/bash

# Task Sorting Script
# A utility script to sort tasks based on different criteria

TASKS_FILE="$(dirname "$0")/tasks.json"

# Check if jq is installed
if ! command -v jq &> /dev/null; then
    echo "Error: jq is required but not installed."
    echo "Please install jq: https://stedolan.github.io/jq/download/"
    exit 1
fi

# Colors for terminal output
GREEN='\033[0;32m'
YELLOW='\033[0;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Function to print sorted tasks
print_sorted_tasks() {
    local sort_type="$1"
    local jq_filter="$2"
    
    echo -e "${BLUE}Sorting tasks by: ${GREEN}$sort_type${NC}"
    echo -e "${CYAN}===============================================${NC}"
    
    jq -r "$jq_filter" "$TASKS_FILE" | column -t -s $'\t'
    
    echo -e "${CYAN}===============================================${NC}"
    echo -e "${YELLOW}Total tasks: $(jq '.tasks | length' "$TASKS_FILE")${NC}"
    echo ""
}

# Function to print usage information
show_usage() {
    echo "Usage: $0 <sort_type>"
    echo ""
    echo "Sort types:"
    echo "  id          - Sort by task ID (ascending)"
    echo "  id-desc     - Sort by task ID (descending)"
    echo "  date        - Sort by creation date (oldest first)"
    echo "  date-desc   - Sort by creation date (newest first)"
    echo "  updated     - Sort by last updated date (oldest first)"
    echo "  updated-desc - Sort by last updated date (newest first)"
    echo "  priority    - Sort by priority (high to low)"
    echo "  status      - Sort by status (completed, in-progress, pending, blocked)"
    echo "  due         - Sort by due date (soonest first)"
    echo "  phase       - Sort by phase"
    echo "  feature     - Sort by feature"
    echo "  assignee    - Sort by assignee"
    echo ""
    echo "Examples:"
    echo "  $0 id         # List tasks sorted by ID ascending"
    echo "  $0 priority   # List tasks sorted by priority"
    echo "  $0 due        # List tasks sorted by due date"
}

# Main function
main() {
    local sort_type="$1"
    
    if [ -z "$sort_type" ]; then
        show_usage
        exit 0
    fi
    
    case "$sort_type" in
        id)
            print_sorted_tasks "ID (ascending)" '.tasks | sort_by(.id) | map("\(.id)\t\(.title)\t[\(.status)]\t\(.priority)\t\(.assignee)\t\(.due_date // "No due date")")[]'
            ;;
        id-desc)
            print_sorted_tasks "ID (descending)" '.tasks | sort_by(-.id) | map("\(.id)\t\(.title)\t[\(.status)]\t\(.priority)\t\(.assignee)\t\(.due_date // "No due date")")[]'
            ;;
        date)
            print_sorted_tasks "Creation Date (oldest first)" '.tasks | sort_by(.created_at) | map("\(.id)\t\(.title)\t[\(.status)]\t\(.created_at)\t\(.assignee)\t\(.due_date // "No due date")")[]'
            ;;
        date-desc)
            print_sorted_tasks "Creation Date (newest first)" '.tasks | sort_by(-.created_at) | map("\(.id)\t\(.title)\t[\(.status)]\t\(.created_at)\t\(.assignee)\t\(.due_date // "No due date")")[]'
            ;;
        updated)
            print_sorted_tasks "Last Updated (oldest first)" '.tasks | sort_by(.updated_at) | map("\(.id)\t\(.title)\t[\(.status)]\t\(.updated_at)\t\(.assignee)\t\(.due_date // "No due date")")[]'
            ;;
        updated-desc)
            print_sorted_tasks "Last Updated (newest first)" '.tasks | sort_by(-.updated_at) | map("\(.id)\t\(.title)\t[\(.status)]\t\(.updated_at)\t\(.assignee)\t\(.due_date // "No due date")")[]'
            ;;
        priority)
            # Convert priority to numeric value for sorting (higher = more important)
            print_sorted_tasks "Priority (high to low)" '.tasks | map(. + {priority_value: (if .priority == "critical" then 4 elif .priority == "high" then 3 elif .priority == "medium" then 2 elif .priority == "low" then 1 else 0 end)}) | sort_by(-.priority_value) | map("\(.id)\t\(.title)\t[\(.status)]\t\(.priority)\t\(.assignee)\t\(.due_date // "No due date")")[]'
            ;;
        status)
            # Custom sort order for status
            print_sorted_tasks "Status" '.tasks | map(. + {status_value: (if .status == "completed" then 4 elif .status == "in-progress" then 3 elif .status == "pending" then 2 elif .status == "blocked" then 1 else 0 end)}) | sort_by(-.status_value) | map("\(.id)\t\(.title)\t[\(.status)]\t\(.priority)\t\(.assignee)\t\(.due_date // "No due date")")[]'
            ;;
        due)
            # Sort by due date, with tasks without due dates at the end
            print_sorted_tasks "Due Date (soonest first)" '.tasks | map(. + {sort_date: (if .due_date == null or .due_date == "" then "9999-12-31" else .due_date end)}) | sort_by(.sort_date) | map("\(.id)\t\(.title)\t[\(.status)]\t\(.priority)\t\(.assignee)\t\(.due_date // "No due date")")[]'
            ;;
        phase)
            print_sorted_tasks "Phase" '.tasks | sort_by(.related_phase) | map("\(.id)\t\(.title)\t[\(.status)]\t\(.related_phase // "No phase")\t\(.assignee)\t\(.due_date // "No due date")")[]'
            ;;
        feature)
            print_sorted_tasks "Feature" '.tasks | sort_by(.related_feature) | map("\(.id)\t\(.title)\t[\(.status)]\t\(.related_feature // "No feature")\t\(.assignee)\t\(.due_date // "No due date")")[]'
            ;;
        assignee)
            print_sorted_tasks "Assignee" '.tasks | sort_by(.assignee) | map("\(.id)\t\(.title)\t[\(.status)]\t\(.priority)\t\(.assignee)\t\(.due_date // "No due date")")[]'
            ;;
        *)
            echo "Unknown sort type: $sort_type"
            show_usage
            exit 1
            ;;
    esac
}

# Call main function with the provided argument
main "$1" 