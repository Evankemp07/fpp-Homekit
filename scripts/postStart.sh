#!/bin/sh

# Start HomeKit service for fpp-Homekit plugin

PLUGIN_DIR="${MEDIADIR}/plugins/fpp-Homekit"
SERVICE_SCRIPT="${PLUGIN_DIR}/scripts/homekit_service.py"
PID_FILE="${PLUGIN_DIR}/scripts/homekit_service.pid"
LOG_FILE="${PLUGIN_DIR}/scripts/homekit_service.log"

# Ensure virtual environment exists and is ready
if [ -z "$VENVDIR" ]; then
    VENVDIR="$PLUGIN_DIR/venv"
fi

ENSURE_VENV()
{
    if [ ! -x "$VENVDIR/bin/python3" ] && [ ! -x "$VENVDIR/bin/python" ]; then
        echo "Virtualenv missing or incomplete. Building venv..."
        if [ -f "$PLUGIN_DIR/scripts/install_venv.sh" ]; then
            sh "$PLUGIN_DIR/scripts/install_venv.sh"
        else
            echo "ERROR: install_venv.sh not found at $PLUGIN_DIR/scripts/install_venv.sh"
            exit 1
        fi
    fi
}

ENSURE_VENV

if [ -x "$VENVDIR/bin/python3" ]; then
    PYTHON3="$VENVDIR/bin/python3"
elif [ -x "$VENVDIR/bin/python" ]; then
    PYTHON3="$VENVDIR/bin/python"
else
    PYTHON3=""
    if command -v python3 >/dev/null 2>&1; then
        PYTHON3="python3"
    elif command -v python >/dev/null 2>&1; then
        PYTHON_VERSION=$(python --version 2>&1 | sed -n 's/.*\([0-9]\+\.[0-9]\+\).*/\1/p' | cut -d. -f1)
        if [ -n "$PYTHON_VERSION" ] && [ "$PYTHON_VERSION" -ge 3 ]; then
            PYTHON3="python"
        fi
    fi

    if [ -z "$PYTHON3" ]; then
        echo "Error: python3 not found. Please install Python 3.6 or newer."
        exit 1
    fi
fi

echo "Using interpreter: $PYTHON3"

# Check if mosquitto is running, try to start it if not
if command -v systemctl >/dev/null 2>&1; then
    if ! systemctl is-active --quiet mosquitto 2>/dev/null; then
        echo "mosquitto not running, attempting to start it..."
        if systemctl start mosquitto 2>/dev/null || sudo systemctl start mosquitto 2>/dev/null; then
            echo "✓ Started mosquitto"
            sleep 0.5
        else
            echo "WARNING: Could not start mosquitto. MQTT control may not work."
            echo "Run: sudo systemctl start mosquitto"
        fi
    fi
fi

# Verify Python can import required modules before starting
echo "Verifying Python dependencies..."
PYTHON_EXEC=$($PYTHON3 -c 'import sys; print(sys.executable)' 2>/dev/null)
echo "Using Python: $PYTHON_EXEC"

# Test import with user site-packages enabled
if ! $PYTHON3 -c "import site; site.ENABLE_USER_SITE = True; import pyhap" 2>/dev/null; then
    echo "ERROR: pyhap module not found. The plugin dependencies may not be installed correctly."
    echo ""
    echo "Python executable: $PYTHON_EXEC"
    echo "Python version: $($PYTHON3 --version 2>&1)"
    echo ""
    echo "Python search path:"
    $PYTHON3 -c "import sys, site; site.ENABLE_USER_SITE = True; print('\\n'.join(sys.path))" 2>/dev/null || \
    $PYTHON3 -c "import sys; print('\\n'.join(sys.path))"
    echo ""
    echo "User site-packages:"
    $PYTHON3 -c "import site; print(site.getusersitepackages())" 2>/dev/null || echo "Could not determine"
    echo ""
    echo "To fix this, try:"
    echo "  1. Reinstall plugin dependencies:"
    echo "     $PYTHON3 -m pip install -r ${PLUGIN_DIR}/scripts/requirements.txt --user --upgrade"
    echo ""
    echo "  2. Or install system-wide (may require sudo):"
    echo "     sudo $PYTHON3 -m pip install -r ${PLUGIN_DIR}/scripts/requirements.txt --upgrade"
    echo ""
    echo "  3. Verify installation:"
    echo "     $PYTHON3 -c 'import pyhap; print(\"HAP-python version:\", pyhap.__version__)'"
    exit 1
fi

echo "✓ Python dependencies verified successfully"

if [ ! -f "${SERVICE_SCRIPT}" ]; then
    echo "HomeKit service script not found: ${SERVICE_SCRIPT}"
    exit 1
fi

# Make sure script is executable
chmod +x "${SERVICE_SCRIPT}" 2>/dev/null

# Kill any existing HomeKit processes to avoid double-binding issues
echo "Ensuring no existing HomeKit service is running..."
if command -v pkill >/dev/null 2>&1; then
    pkill -f homekit_service.py 2>/dev/null || true
    sleep 0.5  # Give processes time to terminate gracefully (reduced)
    pkill -9 -f homekit_service.py 2>/dev/null || true  # Force kill if still running
elif command -v killall >/dev/null 2>&1; then
    killall homekit_service.py 2>/dev/null || true
    sleep 0.5
    killall -9 homekit_service.py 2>/dev/null || true
else
    # Fallback: loop through ps output
    ps aux | grep homekit_service.py | grep -v grep | awk '{print $2}' | while read -r pid; do
        kill "$pid" 2>/dev/null || true
        sleep 1
        kill -9 "$pid" 2>/dev/null || true
    done
fi

# Remove stale PID file if present
rm -f "${PID_FILE}"

# Give system time to fully clean up async tasks from previous instance
echo "Waiting for async cleanup..."
sleep 1

# Start the service in background with logging
echo "Starting FPP HomeKit service..."
cd "${PLUGIN_DIR}/scripts"

# Start with logging to file for debugging
nohup "${PYTHON3}" "${SERVICE_SCRIPT}" >> "${LOG_FILE}" 2>&1 &
START_PID=$!

# Set up trap to clean up if script is interrupted
trap 'echo "Interrupted, cleaning up..."; kill $START_PID 2>/dev/null || true; rm -f "${PID_FILE}"; exit 1' INT TERM

# Give it more time to start up properly (asyncio needs time)
sleep 1

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

