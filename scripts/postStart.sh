#!/bin/sh

# Start HomeKit service for fpp-Homekit plugin

PLUGIN_DIR="${MEDIADIR}/plugins/fpp-Homekit"
SERVICE_SCRIPT="${PLUGIN_DIR}/scripts/homekit_service.py"
PID_FILE="${PLUGIN_DIR}/scripts/homekit_service.pid"

if [ ! -f "${SERVICE_SCRIPT}" ]; then
    echo "HomeKit service script not found: ${SERVICE_SCRIPT}"
    exit 1
fi

# Check if service is already running
if [ -f "${PID_FILE}" ]; then
    OLD_PID=$(cat "${PID_FILE}")
    if ps -p "${OLD_PID}" > /dev/null 2>&1; then
        echo "HomeKit service is already running (PID: ${OLD_PID})"
        exit 0
    else
        # Remove stale PID file
        rm -f "${PID_FILE}"
    fi
fi

# Start the service in background
echo "Starting FPP HomeKit service..."
cd "${PLUGIN_DIR}/scripts"
nohup python3 "${SERVICE_SCRIPT}" > /dev/null 2>&1 &

# Give it a moment to start
sleep 2

# Verify it started
if [ -f "${PID_FILE}" ]; then
    NEW_PID=$(cat "${PID_FILE}")
    if ps -p "${NEW_PID}" > /dev/null 2>&1; then
        echo "FPP HomeKit service started (PID: ${NEW_PID})"
    else
        echo "Warning: HomeKit service may not have started correctly"
    fi
else
    echo "Warning: PID file not created, service may not have started"
fi

