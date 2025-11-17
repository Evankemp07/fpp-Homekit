#!/bin/bash
# Script to update pluginInfo.json SHA to current HEAD

CURRENT_SHA=$(git rev-parse HEAD)
echo "Current SHA: $CURRENT_SHA"

# Update pluginInfo.json
sed -i "s/\"sha\": \"[^\"]*\"/\"sha\": \"$CURRENT_SHA\"/" pluginInfo.json

echo "Updated pluginInfo.json with SHA: $CURRENT_SHA"
echo "Ready to commit and push"
