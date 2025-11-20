#!/bin/bash

# Update script for FPP HomeKit plugin

set -e

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PLUGIN_DIR="${SCRIPT_DIR}/.."

cd "${PLUGIN_DIR}"

# Fix git permissions if needed (common issue on FPP)
if [ -d ".git" ]; then
    # Try to fix permissions on git files
    chmod -R u+w .git 2>/dev/null || true
    # If running as fpp user, try to fix ownership
    if [ "$(id -un)" = "fpp" ]; then
        sudo chown -R fpp:fpp .git 2>/dev/null || true
    fi
fi

git stash >/tmp/fpp-homekit-update-stash.log 2>&1 || true

# Use git fetch + reset instead of pull to avoid ref update issues
git fetch origin master 2>&1 || git fetch origin 2>&1 || true

# Check if we can update
if git rev-parse --verify origin/master >/dev/null 2>&1; then
    # Reset to remote branch (this avoids ref update permission issues)
    git reset --hard origin/master 2>&1 || git reset --hard FETCH_HEAD 2>&1 || true
else
    # Fallback to regular pull
    git pull 2>&1 || true
fi

git stash pop >/tmp/fpp-homekit-update-stash.log 2>&1 || true
