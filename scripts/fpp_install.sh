#!/bin/bash

# fpp-Homekit install script
# Main installer that calls separate helper scripts

# Include common scripts functions and variables
. ${FPPDIR}/scripts/common

PLUGIN_DIR="${MEDIADIR}/plugins/fpp-Homekit"
SCRIPTS_DIR="${PLUGIN_DIR}/scripts"
INSTALL_LOG="${SCRIPTS_DIR}/install.log"

echo "=========================================="
echo "FPP HomeKit Plugin Installation"
echo "=========================================="

# Create log file
echo "Installation log: ${INSTALL_LOG}" > "${INSTALL_LOG}"
echo "Started: $(date)" >> "${INSTALL_LOG}"

# Determine if this is a brand new install (no config or state files)
FIRST_INSTALL=0
if [ ! -f "${SCRIPTS_DIR}/homekit_config.json" ] && [ ! -f "${SCRIPTS_DIR}/homekit_accessory.state" ]; then
    FIRST_INSTALL=1
    echo "Detected first-time installation (no config/state files found)." | tee -a "${INSTALL_LOG}"
else
    echo "Detected existing installation; skipping dependency re-install." | tee -a "${INSTALL_LOG}"
fi

if [ $FIRST_INSTALL -eq 1 ]; then
    # Check system packages
    echo ""
    echo "Step 1: Checking system packages..."
    "${SCRIPTS_DIR}/check_system_packages.sh" | tee -a "${INSTALL_LOG}"

    # Find Python
    echo ""
    echo "Step 2: Finding Python 3..."
    PYTHON_INFO=$("${SCRIPTS_DIR}/find_python.sh" 2>&1)
    if [ $? -ne 0 ]; then
        echo "$PYTHON_INFO" | tee -a "${INSTALL_LOG}"
        exit 1
    fi

    PYTHON3=$(echo "$PYTHON_INFO" | cut -d'|' -f1)
    PYTHON_PATH=$(echo "$PYTHON_INFO" | cut -d'|' -f2)

    echo "Found Python: $PYTHON3"
    $PYTHON3 --version | tee -a "${INSTALL_LOG}"
    echo "Python executable: $PYTHON_PATH" | tee -a "${INSTALL_LOG}"

    # Ensure pip is available via python -m pip
    echo ""
    echo "Step 3: Preparing pip..."
    echo "Ensuring pip is installed via python -m pip..." | tee -a "${INSTALL_LOG}"
    if ! $PYTHON3 -m ensurepip --upgrade >> "${INSTALL_LOG}" 2>&1; then
        echo "Warning: python -m ensurepip failed or not available, continuing..." | tee -a "${INSTALL_LOG}"
    fi

    PIP3="$PYTHON3 -m pip"
    if ! $PIP3 --version >> "${INSTALL_LOG}" 2>&1; then
        echo "ERROR: python -m pip is not available. Please install python3-pip." | tee -a "${INSTALL_LOG}"
        exit 1
    fi
    echo "Using pip command: $PIP3"
    $PIP3 --version | tee -a "${INSTALL_LOG}"

    # Update log with Python and pip info
    echo "Python: $PYTHON3 ($PYTHON_PATH)" >> "${INSTALL_LOG}"
    echo "Pip: $PIP3" >> "${INSTALL_LOG}"

    # Install Python dependencies
    echo ""
    echo "Step 4: Installing Python dependencies..."
    "${SCRIPTS_DIR}/install_python_deps.sh" "${PLUGIN_DIR}" "${PYTHON3}"
    if [ $? -ne 0 ]; then
        echo ""
        echo "ERROR: Failed to install Python dependencies"
        echo "Check the installation log for details: ${INSTALL_LOG}"
        exit 1
    fi
else
    # For updates, still record the Python executable (if available) for logging
    PYTHON_INFO=$("${SCRIPTS_DIR}/find_python.sh" 2>&1)
    PYTHON3=$(echo "$PYTHON_INFO" | cut -d'|' -f1)
    PYTHON_PATH=$(echo "$PYTHON_INFO" | cut -d'|' -f2)
    echo "Python (cached): $PYTHON3 ($PYTHON_PATH)" >> "${INSTALL_LOG}"
fi

# Create data directory for storing pairing information
echo ""
echo "Step 5: Setting up plugin directories..."
DATA_DIR="${SCRIPTS_DIR}"
mkdir -p "${DATA_DIR}"
echo "✓ Created data directory: ${DATA_DIR}"

# Make homekit_service.py executable
if [ -f "${PLUGIN_DIR}/scripts/homekit_service.py" ]; then
    chmod +x "${PLUGIN_DIR}/scripts/homekit_service.py"
    echo "✓ Made homekit_service.py executable"
    
    # Verify the shebang line uses the correct Python
    SHEBANG=$(head -1 "${PLUGIN_DIR}/scripts/homekit_service.py")
    if [[ "$SHEBANG" == *"python3"* ]] || [[ "$SHEBANG" == *"python"* ]]; then
        echo "✓ Service script shebang verified: $SHEBANG"
    else
        echo "Warning: Service script may not have correct shebang"
    fi
else
    echo "WARNING: homekit_service.py not found at ${PLUGIN_DIR}/scripts/homekit_service.py"
fi

# Detect FPP API port
echo ""
echo "Step 6: Detecting FPP API port..."
FPP_API_PORT=""
FPP_API_HOST="localhost"

# Try to read from FPP settings file
SETTINGS_PATHS=(
    "${FPPDIR}/media/settings"
    "/home/fpp/media/settings"
    "/opt/fpp/media/settings"
    "${MEDIADIR}/settings"
)

for settings_file in "${SETTINGS_PATHS[@]}"; do
    if [ -f "${settings_file}" ] && [ -r "${settings_file}" ]; then
        HTTP_PORT=$(grep -E "^HTTPPort\s*=" "${settings_file}" 2>/dev/null | head -1 | sed 's/.*=\s*\([0-9]*\).*/\1/')
        if [ -n "${HTTP_PORT}" ] && [ "${HTTP_PORT}" -gt 0 ] 2>/dev/null; then
            FPP_API_PORT="${HTTP_PORT}"
            echo "Found HTTP port ${FPP_API_PORT} in ${settings_file}" | tee -a "${INSTALL_LOG}"
            break
        fi
    fi
done

# If not found in config, test common ports
if [ -z "${FPP_API_PORT}" ]; then
    echo "Testing common FPP API ports..." | tee -a "${INSTALL_LOG}"
    TEST_PORTS=(32320 80 8080)
    
    for port in "${TEST_PORTS[@]}"; do
        # Try to connect to /api/status endpoint
        if command -v curl >/dev/null 2>&1; then
            response=$(curl -s -o /dev/null -w "%{http_code}" --connect-timeout 2 --max-time 3 "http://localhost:${port}/api/status" 2>/dev/null)
            if [ "${response}" = "200" ]; then
                FPP_API_PORT="${port}"
                echo "✓ Found FPP API on port ${port}" | tee -a "${INSTALL_LOG}"
                break
            fi
        elif command -v wget >/dev/null 2>&1; then
            if wget -q --spider --timeout=2 "http://localhost:${port}/api/status" 2>/dev/null; then
                FPP_API_PORT="${port}"
                echo "✓ Found FPP API on port ${port}" | tee -a "${INSTALL_LOG}"
                break
            fi
        fi
    done
fi

# Save detected port to config file for PHP to read
API_CONFIG_FILE="${SCRIPTS_DIR}/fpp_api_config.json"
if [ -n "${FPP_API_PORT}" ]; then
    echo "{\"host\": \"${FPP_API_HOST}\", \"port\": ${FPP_API_PORT}}" > "${API_CONFIG_FILE}"
    echo "✓ Saved API configuration: ${FPP_API_HOST}:${FPP_API_PORT}" | tee -a "${INSTALL_LOG}"
else
    echo "WARNING: Could not detect FPP API port. Using default 32320." | tee -a "${INSTALL_LOG}"
    echo "{\"host\": \"${FPP_API_HOST}\", \"port\": 32320}" > "${API_CONFIG_FILE}"
fi

# Check if avahi-daemon is running
echo ""
echo "Step 7: Checking avahi-daemon..."
if command -v systemctl >/dev/null 2>&1; then
    if systemctl is-active --quiet avahi-daemon; then
        echo "✓ avahi-daemon is running"
    else
        echo "WARNING: avahi-daemon is not running. HomeKit discovery may not work."
        echo "Start it with: sudo systemctl start avahi-daemon"
        echo "Enable it at boot with: sudo systemctl enable avahi-daemon"
    fi
fi

# Restart service if it was running (for updates)
if [ $FIRST_INSTALL -eq 0 ]; then
    PID_FILE="${SCRIPTS_DIR}/homekit_service.pid"
    SERVICE_WAS_RUNNING=0
    
    if [ -f "${PID_FILE}" ]; then
        OLD_PID=$(cat "${PID_FILE}" 2>/dev/null)
        if [ -n "$OLD_PID" ]; then
            # Check if process is running
            if command -v ps >/dev/null 2>&1; then
                if ps -p "${OLD_PID}" > /dev/null 2>&1; then
                    SERVICE_WAS_RUNNING=1
                fi
            elif [ -f "/proc/${OLD_PID}" ]; then
                SERVICE_WAS_RUNNING=1
            fi
        fi
    fi
    
    if [ $SERVICE_WAS_RUNNING -eq 1 ]; then
        echo ""
        echo "Step 8: Restarting HomeKit service after update..."
        # Stop the old service first
        if [ -f "${SCRIPTS_DIR}/postStop.sh" ]; then
            "${SCRIPTS_DIR}/postStop.sh" >> "${INSTALL_LOG}" 2>&1
        fi
        sleep 1
        # Start the new service
        if [ -f "${SCRIPTS_DIR}/postStart.sh" ]; then
            "${SCRIPTS_DIR}/postStart.sh" >> "${INSTALL_LOG}" 2>&1
            echo "✓ Service restarted after update"
        fi
    fi
fi

echo ""
echo "=========================================="
echo "FPP HomeKit plugin installation complete!"
echo "=========================================="
echo ""
echo "Next steps:"
echo "1. Open the plugin Status page and choose the playlist to start"
echo "2. Scan the HomeKit QR code shown on the Status page"
echo "3. Pair with the Home app and control playback from HomeKit"
echo ""
echo "Installation log saved to: ${INSTALL_LOG}"
echo ""
