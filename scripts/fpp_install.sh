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
    if [ -n "$PIP3" ]; then
        # Upgrade pip first to ensure it can handle the requirements
        echo "Upgrading pip..."
        $PIP3 install --upgrade pip --user 2>/dev/null || $PIP3 install --upgrade pip 2>/dev/null || true
        
        # Try with --user first (preferred for non-root installs)
        echo "Installing Python packages..."
        $PIP3 install -r "${REQUIREMENTS_FILE}" --user 2>&1
        if [ $? -ne 0 ]; then
            echo "Warning: --user install failed, trying without --user..."
            # Try without --user (may require sudo, but let user handle that)
            $PIP3 install -r "${REQUIREMENTS_FILE}" 2>&1 || {
                echo "ERROR: Failed to install Python dependencies."
                echo "You may need to run: sudo $PIP3 install -r ${REQUIREMENTS_FILE}"
                echo "Or ensure all system dependencies are installed first."
                exit 1
            }
        else
            echo "Python dependencies installed successfully."
        fi
    else
        echo "ERROR: pip3 not found. Please install Python 3 and pip3."
        echo "On Debian/Ubuntu: sudo apt-get install python3-pip"
        echo "On macOS: brew install python3"
        exit 1
    fi
else
    echo "ERROR: requirements.txt not found at ${REQUIREMENTS_FILE}"
    exit 1
fi

# Verify critical Python packages are installed
echo "Verifying Python package installation..."
python3 -c "import pyhap; import requests; import qrcode; from PIL import Image; print('All Python packages verified successfully.')" 2>&1
if [ $? -ne 0 ]; then
    echo "WARNING: Some Python packages may not be installed correctly."
    echo "Try running: python3 -c 'import pyhap; import requests; import qrcode; from PIL import Image'"
    echo "to see which package is missing."
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

