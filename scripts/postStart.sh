#!/bin/sh

# Start HomeKit service for fpp-Homekit plugin

PLUGIN_DIR="${MEDIADIR}/plugins/fpp-Homekit"
SERVICE_SCRIPT="${PLUGIN_DIR}/scripts/homekit_service.py"
PID_FILE="${PLUGIN_DIR}/scripts/homekit_service.pid"
LOG_FILE="${PLUGIN_DIR}/scripts/homekit_service.log"

# Find python3 (must match the one used during installation)
PYTHON3=""
if command -v python3 >/dev/null 2>&1; then
    PYTHON3="python3"
elif command -v python >/dev/null 2>&1; then
    # Check if python is python3
    PYTHON_VERSION=$(python --version 2>&1 | sed -n 's/.*\([0-9]\+\.[0-9]\+\).*/\1/p' | cut -d. -f1)
    if [ -n "$PYTHON_VERSION" ] && [ "$PYTHON_VERSION" -ge 3 ]; then
        PYTHON3="python"
    fi
fi

if [ -z "$PYTHON3" ]; then
    echo "Error: python3 not found. Please install Python 3.6 or newer."
    exit 1
fi

# Verify Python can import required modules before starting
echo "Verifying Python dependencies..."
if ! $PYTHON3 -c "import pyhap" 2>/dev/null; then
    echo "ERROR: pyhap module not found. The plugin dependencies may not be installed correctly."
    echo "Python executable: $($PYTHON3 -c 'import sys; print(sys.executable)')"
    echo "Python path:"
    $PYTHON3 -c "import sys; print('\\n'.join(sys.path))"
    echo ""
    echo "Try reinstalling the plugin or manually install dependencies:"
    echo "  pip3 install -r ${PLUGIN_DIR}/scripts/requirements.txt"
    exit 1
fi

echo "Python dependencies verified successfully"

if [ ! -f "${SERVICE_SCRIPT}" ]; then
    echo "HomeKit service script not found: ${SERVICE_SCRIPT}"
    exit 1
fi

# Make sure script is executable
chmod +x "${SERVICE_SCRIPT}" 2>/dev/null

# Check if service is already running
if [ -f "${PID_FILE}" ]; then
    OLD_PID=$(cat "${PID_FILE}" 2>/dev/null)
    if [ -n "$OLD_PID" ] && ps -p "${OLD_PID}" > /dev/null 2>&1; then
        echo "HomeKit service is already running (PID: ${OLD_PID})"
        exit 0
    else
        # Remove stale PID file
        rm -f "${PID_FILE}"
    fi
fi

# Start the service in background with logging
echo "Starting FPP HomeKit service..."
cd "${PLUGIN_DIR}/scripts"

# Start with logging to file for debugging
nohup "${PYTHON3}" "${SERVICE_SCRIPT}" >> "${LOG_FILE}" 2>&1 &
START_PID=$!

# Give it a moment to start
sleep 3

# Verify it started
if [ -f "${PID_FILE}" ]; then
    NEW_PID=$(cat "${PID_FILE}" 2>/dev/null)
    if [ -n "$NEW_PID" ] && ps -p "${NEW_PID}" > /dev/null 2>&1; then
        echo "FPP HomeKit service started successfully (PID: ${NEW_PID})"
    else
        echo "Warning: HomeKit service PID file exists but process not found"
        echo "Check log file: ${LOG_FILE}"
        # Show last few lines of log
        if [ -f "${LOG_FILE}" ]; then
            echo "Last log entries:"
            tail -5 "${LOG_FILE}" 2>/dev/null || true
        fi
    fi
else
    echo "Error: PID file not created, service may have failed to start"
    echo "Check log file: ${LOG_FILE}"
    # Show last few lines of log
    if [ -f "${LOG_FILE}" ]; then
        echo "Last log entries:"
        tail -10 "${LOG_FILE}" 2>/dev/null || true
    fi
    exit 1
fi

