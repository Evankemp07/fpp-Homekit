#!/bin/bash
# HomeKit Plugin Update Script

echo "=== FPP HomeKit Plugin Update ==="
echo "Directory: $(pwd)"

# Stash any local changes
echo "Stashing local changes..."
git stash

# Pull latest changes
echo "Pulling latest changes..."
git pull

# Restore local changes (like config files)
echo "Restoring local changes..."
git stash pop

# Show current version
echo "Current version:"
git log --oneline -1

echo "=== Update Complete ==="
echo "Restart HomeKit service in FPP UI if needed."
