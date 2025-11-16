#!/bin/bash

# fpp-Homekit uninstall script

# Include common scripts functions and variables
. ${FPPDIR}/scripts/common

PLUGIN_DIR="${MEDIADIR}/plugins/fpp-Homekit"

# Stop the HomeKit service if running
if [ -f "${PLUGIN_DIR}/scripts/homekit_service.pid" ]; then
    PID=$(cat "${PLUGIN_DIR}/scripts/homekit_service.pid")
    if ps -p "${PID}" > /dev/null 2>&1; then
        echo "Stopping HomeKit service..."
        kill "${PID}"
        sleep 2
        if ps -p "${PID}" > /dev/null 2>&1; then
            kill -9 "${PID}"
        fi
    fi
fi

# Clean up service files
rm -f "${PLUGIN_DIR}/scripts/homekit_service.pid"
rm -f "${PLUGIN_DIR}/scripts/homekit_accessory.state"
rm -f "${PLUGIN_DIR}/scripts/homekit_pairing_info.json"
rm -f "${PLUGIN_DIR}/scripts/homekit_config.json"

echo "FPP HomeKit plugin uninstalled."
