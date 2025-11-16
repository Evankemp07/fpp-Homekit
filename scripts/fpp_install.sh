#!/bin/bash

# fpp-Homekit install script

# Include common scripts functions and variables
. ${FPPDIR}/scripts/common

# Install Python dependencies
PLUGIN_DIR="${MEDIADIR}/plugins/fpp-Homekit"
REQUIREMENTS_FILE="${PLUGIN_DIR}/scripts/requirements.txt"

# Find pip3
PIP3=""
if command -v pip3 >/dev/null 2>&1; then
    PIP3="pip3"
elif command -v pip >/dev/null 2>&1; then
    PIP3="pip"
fi

if [ -f "${REQUIREMENTS_FILE}" ]; then
    echo "Installing Python dependencies for HomeKit plugin..."
    if [ -n "$PIP3" ]; then
        # Try with --user first
        $PIP3 install -r "${REQUIREMENTS_FILE}" --user 2>&1
        if [ $? -ne 0 ]; then
            echo "Warning: --user install failed, trying without --user..."
            # Try without --user (may require sudo, but let user handle that)
            $PIP3 install -r "${REQUIREMENTS_FILE}" 2>&1 || echo "Warning: Failed to install Python dependencies. You may need to install them manually or run with sudo."
        fi
    else
        echo "Error: pip3 not found. Please install Python 3 and pip3."
        echo "On Debian/Ubuntu: sudo apt-get install python3-pip"
        echo "On macOS: brew install python3"
    fi
else
    echo "Warning: requirements.txt not found at ${REQUIREMENTS_FILE}"
fi

# Create data directory for storing pairing information
DATA_DIR="${PLUGIN_DIR}/scripts"
mkdir -p "${DATA_DIR}"

# Make homekit_service.py executable
if [ -f "${PLUGIN_DIR}/scripts/homekit_service.py" ]; then
    chmod +x "${PLUGIN_DIR}/scripts/homekit_service.py"
fi

echo "FPP HomeKit plugin installation complete."

