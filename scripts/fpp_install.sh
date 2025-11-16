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

# Check if avahi-daemon is running
echo ""
echo "Step 6: Checking avahi-daemon..."
if command -v systemctl >/dev/null 2>&1; then
    if systemctl is-active --quiet avahi-daemon; then
        echo "✓ avahi-daemon is running"
    else
        echo "WARNING: avahi-daemon is not running. HomeKit discovery may not work."
        echo "Start it with: sudo systemctl start avahi-daemon"
        echo "Enable it at boot with: sudo systemctl enable avahi-daemon"
    fi
fi

echo ""
echo "=========================================="
echo "FPP HomeKit plugin installation complete!"
echo "=========================================="
echo ""
echo "Next steps:"
echo "1. Go to the plugin Configuration page to select a playlist"
echo "2. Go to the plugin Status page to view the QR code"
echo "3. Pair with HomeKit using the Home app on your iOS device"
echo ""
echo "Installation log saved to: ${INSTALL_LOG}"
echo ""
