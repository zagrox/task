#!/bin/bash
# scripts/new-feature.sh
# Purpose: Automate feature branch creation and tracking

if [ $# -lt 1 ]; then
  echo "Usage: ./new-feature.sh feature-name [roadmap-phase]"
  exit 1
fi

FEATURE=$1
PHASE=${2:-"unspecified"}
DATE=$(date +"%Y%m%d")
BRANCH="feature/${DATE}_${PHASE}_${FEATURE}"

# Check if jq is installed
if ! command -v jq &> /dev/null; then
  echo "Error: jq is required but not installed."
  echo "Install with: brew install jq"
  exit 1
fi

# Create branch
git checkout -b $BRANCH main
echo "Created branch $BRANCH"

# Record in features.json
if [ ! -f features.json ]; then
  echo '{"features":[]}' > features.json
fi

# Add feature to JSON with optimized indexing
TMP=$(mktemp)
jq --arg feature "$FEATURE" \
   --arg phase "$PHASE" \
   --arg branch "$BRANCH" \
   --arg date "$(date +"%Y-%m-%d")" \
   '.features += [{"name":$feature,"phase":$phase,"branch":$branch,"created":$date,"status":"in-progress"}] |
    .index = (.features | map({key: .name, value: (.features | length - 1)}) | from_entries)' \
   features.json > "$TMP" && mv "$TMP" features.json

git add features.json
git commit -m "Start feature: $FEATURE (Phase: $PHASE) [REF:$PHASE]"

echo "Feature tracking initialized in features.json"
echo "Branch: $BRANCH" 