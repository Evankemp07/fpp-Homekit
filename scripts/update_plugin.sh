#!/bin/bash

# Update script for FPP HomeKit plugin

set -e

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PLUGIN_DIR="${SCRIPT_DIR}/.."

cd "${PLUGIN_DIR}"

git stash >/tmp/fpp-homekit-update-stash.log 2>&1 || true
git pull >/dev/null 2>&1
git stash pop >/tmp/fpp-homekit-update-stash.log 2>&1 || true
