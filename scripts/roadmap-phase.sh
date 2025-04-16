#!/bin/bash
# scripts/roadmap-phase.sh
# Purpose: Manage project roadmap phases with Git integration

if [ $# -lt 1 ]; then
  echo "Usage: ./roadmap-phase.sh phase-name \"Description\""
  exit 1
fi

PHASE=$1
DESC=$2
ROADMAP_FILE="roadmap.json"
PHASE_REF="P$(grep -c "phase" "$ROADMAP_FILE" 2>/dev/null || echo "1")-${PHASE^^}"
PHASE_REF=$(echo "$PHASE_REF" | tr -cd '[:alnum:]-')

# Check if jq is installed
if ! command -v jq &> /dev/null; then
  echo "Error: jq is required but not installed."
  echo "Install with: brew install jq"
  exit 1
fi

# Create roadmap file if it doesn't exist with optimized structure
if [ ! -f "$ROADMAP_FILE" ]; then
  echo '{
    "phases": [],
    "current_phase": null,
    "indexes": {
      "by_name": {},
      "by_ref": {}
    }
  }' > "$ROADMAP_FILE"
fi

# Add new phase with performance optimizations
echo "Adding phase $PHASE ($PHASE_REF) to roadmap..."
TMP=$(mktemp)

# Update JSON with indexing for faster lookups
jq --arg phase "$PHASE" \
   --arg desc "$DESC" \
   --arg date "$(date +"%Y-%m-%d")" \
   --arg ref "$PHASE_REF" \
   '.phases += [{
     "name": $phase,
     "ref": $ref,
     "description": $desc,
     "start_date": $date,
     "features": [],
     "completed": false
   }] | 
   .current_phase = $ref |
   .indexes.by_name[$phase] = (.phases | length - 1) |
   .indexes.by_ref[$ref] = (.phases | length - 1)' \
   "$ROADMAP_FILE" > "$TMP" && mv "$TMP" "$ROADMAP_FILE"

# Create documentation directory for this phase
DOCS_DIR="docs/roadmap"
mkdir -p "$DOCS_DIR"

# Create a markdown file for the phase details
PHASE_FILE="$DOCS_DIR/$(echo "$PHASE" | tr '[:upper:]' '[:lower:]' | tr ' ' '-').md"

echo "# Phase: $PHASE

## Reference ID: \`$PHASE_REF\`

## Description
$DESC

## Start Date
$(date +"%Y-%m-%d")

## Features
*No features have been added to this phase yet.*

## Milestones
*Milestones will be added as the phase progresses.*

## AI Assistant Tasks
- Track phase progress [REF:AI-RM-001]
- Monitor feature implementation [REF:AI-FT-003]
- Provide regular status updates [REF:AI-RC-006]
" > "$PHASE_FILE"

# Create branch for the phase with better naming
BRANCH_NAME="phase/$(echo "$PHASE" | tr '[:upper:]' '[:lower:]' | tr ' ' '-')"
git checkout -b "$BRANCH_NAME"

# Add files to git
git add "$ROADMAP_FILE" "$PHASE_FILE"
git commit -m "Start roadmap phase: $PHASE [REF:$PHASE_REF]"

echo "✅ Phase $PHASE ($PHASE_REF) initialized in $ROADMAP_FILE"
echo "✅ Phase documentation created at $PHASE_FILE"
echo "✅ Phase branch created: $BRANCH_NAME" 