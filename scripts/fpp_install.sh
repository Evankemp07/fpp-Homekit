#!/bin/bash

# fpp-Homekit install script

# Include common scripts functions and variables
. ${FPPDIR}/scripts/common

# Install Python dependencies
PLUGIN_DIR="${MEDIADIR}/plugins/fpp-Homekit"
REQUIREMENTS_FILE="${PLUGIN_DIR}/scripts/requirements.txt"

# Check if running on a Debian-based system (Raspberry Pi, etc.)
if [ -f /etc/debian_version ]; then
    echo "Checking for required system packages..."
    
    # List of required packages
    REQUIRED_PACKAGES="python3 python3-pip python3-dev avahi-daemon libavahi-compat-libdnssd-dev libffi-dev libssl-dev build-essential libjpeg-dev zlib1g-dev libfreetype6-dev liblcms2-dev"
    
    MISSING_PACKAGES=""
    for pkg in $REQUIRED_PACKAGES; do
        if ! dpkg -l | grep -q "^ii  $pkg "; then
            MISSING_PACKAGES="$MISSING_PACKAGES $pkg"
        fi
    done
    
    if [ -n "$MISSING_PACKAGES" ]; then
        echo "Warning: Some required system packages may be missing: $MISSING_PACKAGES"
        echo "These should be installed via pluginInfo.json dependencies, but if installation fails,"
        echo "you may need to run: sudo apt-get update && sudo apt-get install $MISSING_PACKAGES"
    else
        echo "All required system packages are installed."
    fi
fi

# Find pip3
PIP3=""
if command -v pip3 >/dev/null 2>&1; then
    PIP3="pip3"
elif command -v pip >/dev/null 2>&1; then
    PIP3="pip"
fi

if [ -f "${REQUIREMENTS_FILE}" ]; then
    echo "Installing Python dependencies for HomeKit plugin..."
    
    # Create log file for detailed error output
    INSTALL_LOG="${PLUGIN_DIR}/scripts/install.log"
    echo "Installation log: ${INSTALL_LOG}" > "${INSTALL_LOG}"
    echo "Started: $(date)" >> "${INSTALL_LOG}"
    
    if [ -n "$PIP3" ]; then
        echo "Using pip: $PIP3" | tee -a "${INSTALL_LOG}"
        echo "Python version: $(python3 --version 2>&1)" | tee -a "${INSTALL_LOG}"
        
        # Upgrade pip first to ensure it can handle the requirements
        echo "Upgrading pip..." | tee -a "${INSTALL_LOG}"
        $PIP3 install --upgrade pip --user >> "${INSTALL_LOG}" 2>&1 || \
        $PIP3 install --upgrade pip >> "${INSTALL_LOG}" 2>&1 || \
        echo "Warning: Could not upgrade pip, continuing anyway..." | tee -a "${INSTALL_LOG}"
        
        # Try with --user first (preferred for non-root installs)
        echo "Installing Python packages with --user flag..." | tee -a "${INSTALL_LOG}"
        INSTALL_SUCCESS=0
        
        if $PIP3 install -r "${REQUIREMENTS_FILE}" --user >> "${INSTALL_LOG}" 2>&1; then
            echo "Python dependencies installed successfully with --user flag." | tee -a "${INSTALL_LOG}"
            INSTALL_SUCCESS=1
        else
            echo "Warning: --user install failed, trying without --user..." | tee -a "${INSTALL_LOG}"
            echo "Last 20 lines of install log:" | tee -a "${INSTALL_LOG}"
            tail -20 "${INSTALL_LOG}" | tee -a "${INSTALL_LOG}"
            
            # Try without --user (may require sudo, but let user handle that)
            if $PIP3 install -r "${REQUIREMENTS_FILE}" >> "${INSTALL_LOG}" 2>&1; then
                echo "Python dependencies installed successfully without --user flag." | tee -a "${INSTALL_LOG}"
                INSTALL_SUCCESS=1
            else
                echo "ERROR: Failed to install Python dependencies." | tee -a "${INSTALL_LOG}"
                echo "Last 30 lines of error output:" | tee -a "${INSTALL_LOG}"
                tail -30 "${INSTALL_LOG}"
                echo ""
                echo "Full error log saved to: ${INSTALL_LOG}"
                echo ""
                echo "To install manually, try:"
                echo "  sudo $PIP3 install -r ${REQUIREMENTS_FILE}"
                echo ""
                echo "Or ensure all system dependencies are installed first:"
                echo "  sudo apt-get update"
                echo "  sudo apt-get install python3 python3-pip python3-dev libffi-dev libssl-dev build-essential libjpeg-dev zlib1g-dev libfreetype6-dev liblcms2-dev"
                INSTALL_SUCCESS=0
            fi
        fi
        
        if [ $INSTALL_SUCCESS -eq 0 ]; then
            echo "Warning: Failed to install some Python dependencies. Check ${INSTALL_LOG} for details."
        fi
    else
        echo "ERROR: pip3 not found. Please install Python 3 and pip3." | tee -a "${INSTALL_LOG}"
        echo "On Debian/Ubuntu: sudo apt-get install python3-pip" | tee -a "${INSTALL_LOG}"
        echo "On macOS: brew install python3" | tee -a "${INSTALL_LOG}"
        exit 1
    fi
else
    echo "ERROR: requirements.txt not found at ${REQUIREMENTS_FILE}"
    exit 1
fi

# Verify critical Python packages are installed
echo "Verifying Python package installation..."
VERIFY_OUTPUT=$(python3 -c "import pyhap; import requests; import qrcode; from PIL import Image; print('All Python packages verified successfully.')" 2>&1)
VERIFY_RESULT=$?

if [ -n "${INSTALL_LOG}" ]; then
    echo "Verifying Python package installation..." >> "${INSTALL_LOG}"
fi

if [ $VERIFY_RESULT -eq 0 ]; then
    echo "$VERIFY_OUTPUT"
    if [ -n "${INSTALL_LOG}" ]; then
        echo "$VERIFY_OUTPUT" >> "${INSTALL_LOG}"
    fi
else
    echo "WARNING: Some Python packages may not be installed correctly."
    echo "$VERIFY_OUTPUT"
    echo ""
    echo "Missing packages detected. Try running manually to see which package is missing:"
    echo "  python3 -c 'import pyhap; import requests; import qrcode; from PIL import Image'"
    if [ -n "${INSTALL_LOG}" ]; then
        echo "WARNING: Some Python packages may not be installed correctly." >> "${INSTALL_LOG}"
        echo "$VERIFY_OUTPUT" >> "${INSTALL_LOG}"
        echo "Check ${INSTALL_LOG} for detailed installation errors."
    fi
fi

# Create data directory for storing pairing information
DATA_DIR="${PLUGIN_DIR}/scripts"
mkdir -p "${DATA_DIR}"

# Make homekit_service.py executable
if [ -f "${PLUGIN_DIR}/scripts/homekit_service.py" ]; then
    chmod +x "${PLUGIN_DIR}/scripts/homekit_service.py"
fi

# Check if avahi-daemon is running
if command -v systemctl >/dev/null 2>&1; then
    if systemctl is-active --quiet avahi-daemon; then
        echo "avahi-daemon is running."
    else
        echo "WARNING: avahi-daemon is not running. HomeKit discovery may not work."
        echo "Start it with: sudo systemctl start avahi-daemon"
        echo "Enable it at boot with: sudo systemctl enable avahi-daemon"
    fi
fi

echo "FPP HomeKit plugin installation complete."
