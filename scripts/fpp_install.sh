#!/bin/bash

# fpp-Homekit install script

# Include common scripts functions and variables
. ${FPPDIR}/scripts/common

# Install Python dependencies
PLUGIN_DIR="${MEDIADIR}/plugins/fpp-Homekit"
REQUIREMENTS_FILE="${PLUGIN_DIR}/scripts/requirements.txt"

if [ -f "${REQUIREMENTS_FILE}" ]; then
    echo "Installing Python dependencies for HomeKit plugin..."
    pip3 install -r "${REQUIREMENTS_FILE}" --user
    if [ $? -ne 0 ]; then
        echo "Warning: Failed to install some Python dependencies. You may need to install them manually."
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

