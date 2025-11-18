#!/bin/bash

# Update script for FPP HomeKit plugin

set -e

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PLUGIN_DIR="${SCRIPT_DIR}/.."

echo "=== FPP HomeKit Plugin Update ==="
echo "Plugin directory: ${PLUGIN_DIR}"

cd "${PLUGIN_DIR}"

echo "Stashing local changes (if any)..."
git stash >/tmp/fpp-homekit-update-stash.log 2>&1 || true

echo "Pulling latest changes..."
git pull

echo "Restoring local changes (if any)..."
git stash pop >/tmp/fpp-homekit-update-stash.log 2>&1 || true

echo "Current version:"
git log --oneline -1

echo "=== Update complete ==="
echo "If the HomeKit service is running, restart it from the plugin UI to apply changes."
echo ""
echo "made with ❤️ in PA"
