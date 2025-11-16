#!/bin/bash

# fpp-Homekit install script

# Include common scripts functions and variables
. ${FPPDIR}/scripts/common

PLUGIN_DIR="${MEDIADIR}/plugins/fpp-Homekit"
REQUIREMENTS_FILE="${PLUGIN_DIR}/scripts/requirements.txt"

echo "=========================================="
echo "FPP HomeKit Plugin Installation"
echo "=========================================="

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

# Find python3
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
    echo "ERROR: python3 not found. Please install Python 3.6 or newer."
    echo "On Debian/Ubuntu: sudo apt-get install python3 python3-pip"
    echo "On macOS: brew install python3"
    exit 1
fi

echo "Found Python: $PYTHON3"
$PYTHON3 --version

# Find pip3 (prefer pip3 associated with python3)
PIP3=""
if command -v pip3 >/dev/null 2>&1; then
    PIP3="pip3"
elif $PYTHON3 -m pip --version >/dev/null 2>&1; then
    PIP3="$PYTHON3 -m pip"
elif command -v pip >/dev/null 2>&1; then
    PIP3="pip"
fi

if [ -z "$PIP3" ]; then
    echo "ERROR: pip3 not found. Please install pip3."
    echo "On Debian/Ubuntu: sudo apt-get install python3-pip"
    echo "On macOS: python3 -m ensurepip --upgrade"
    exit 1
fi

echo "Found pip: $PIP3"
$PIP3 --version

# Install Python dependencies
if [ -f "${REQUIREMENTS_FILE}" ]; then
    echo ""
    echo "Installing Python dependencies from requirements.txt..."
    echo "This may take a few minutes..."
    
    # Create log file for detailed error output
    INSTALL_LOG="${PLUGIN_DIR}/scripts/install.log"
    echo "Installation log: ${INSTALL_LOG}" > "${INSTALL_LOG}"
    echo "Started: $(date)" >> "${INSTALL_LOG}"
    echo "Using pip: $PIP3" >> "${INSTALL_LOG}"
    echo "Python version: $($PYTHON3 --version 2>&1)" >> "${INSTALL_LOG}"
    
    INSTALL_SUCCESS=0
    
    # Upgrade pip first to ensure it can handle the requirements
    echo "Upgrading pip..." | tee -a "${INSTALL_LOG}"
    $PIP3 install --upgrade pip --user >> "${INSTALL_LOG}" 2>&1 || \
    $PIP3 install --upgrade pip >> "${INSTALL_LOG}" 2>&1 || \
    echo "Warning: Could not upgrade pip, continuing anyway..." | tee -a "${INSTALL_LOG}"
    
    # Try with --user first (recommended, doesn't require root)
    echo "Attempting installation with --user flag..." | tee -a "${INSTALL_LOG}"
    if $PIP3 install -r "${REQUIREMENTS_FILE}" --user --upgrade >> "${INSTALL_LOG}" 2>&1; then
        INSTALL_SUCCESS=1
        echo "✓ Dependencies installed successfully with --user flag" | tee -a "${INSTALL_LOG}"
    else
        echo "Warning: --user install failed, trying system-wide installation..." | tee -a "${INSTALL_LOG}"
        echo "Last 20 lines of install log:" | tee -a "${INSTALL_LOG}"
        tail -20 "${INSTALL_LOG}" | tee -a "${INSTALL_LOG}"
        
        # Try without --user (may require sudo)
        if $PIP3 install -r "${REQUIREMENTS_FILE}" --upgrade >> "${INSTALL_LOG}" 2>&1; then
            INSTALL_SUCCESS=1
            echo "✓ Dependencies installed successfully system-wide" | tee -a "${INSTALL_LOG}"
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
    
    # Verify critical dependencies are installed
    echo ""
    echo "Verifying installed dependencies..."
    MISSING_DEPS=0
    
    if ! $PYTHON3 -c "import pyhap" 2>/dev/null; then
        echo "  ✗ HAP-python not found"
        MISSING_DEPS=1
    else
        echo "  ✓ HAP-python installed"
    fi
    
    if ! $PYTHON3 -c "import requests" 2>/dev/null; then
        echo "  ✗ requests not found"
        MISSING_DEPS=1
    else
        echo "  ✓ requests installed"
    fi
    
    if ! $PYTHON3 -c "import qrcode" 2>/dev/null; then
        echo "  ✗ qrcode not found"
        MISSING_DEPS=1
    else
        echo "  ✓ qrcode installed"
    fi
    
    if ! $PYTHON3 -c "from PIL import Image" 2>/dev/null; then
        echo "  ✗ Pillow not found"
        MISSING_DEPS=1
    else
        echo "  ✓ Pillow installed"
    fi
    
    if [ $MISSING_DEPS -eq 1 ]; then
        echo ""
        echo "WARNING: Some dependencies are missing. The plugin may not work correctly."
        echo "Try installing manually:"
        echo "  $PIP3 install -r ${REQUIREMENTS_FILE}"
        echo "Check ${INSTALL_LOG} for detailed installation errors."
    else
        echo ""
        echo "✓ All Python dependencies verified successfully"
    fi
    
    if [ $INSTALL_SUCCESS -eq 0 ]; then
        echo "Warning: Failed to install some Python dependencies. Check ${INSTALL_LOG} for details."
    fi
else
    echo "ERROR: requirements.txt not found at ${REQUIREMENTS_FILE}"
    exit 1
fi

# Create data directory for storing pairing information
DATA_DIR="${PLUGIN_DIR}/scripts"
mkdir -p "${DATA_DIR}"
echo "✓ Created data directory: ${DATA_DIR}"

# Make homekit_service.py executable
if [ -f "${PLUGIN_DIR}/scripts/homekit_service.py" ]; then
    chmod +x "${PLUGIN_DIR}/scripts/homekit_service.py"
    echo "✓ Made homekit_service.py executable"
else
    echo "WARNING: homekit_service.py not found at ${PLUGIN_DIR}/scripts/homekit_service.py"
fi

# Check if avahi-daemon is running
if command -v systemctl >/dev/null 2>&1; then
    if systemctl is-active --quiet avahi-daemon; then
        echo "✓ avahi-daemon is running."
    else
        echo "WARNING: avahi-daemon is not running. HomeKit discovery may not work."
        echo "Start it with: sudo systemctl start avahi-daemon"
        echo "Enable it at boot with: sudo systemctl enable avahi-daemon"
    fi
fi

echo ""
echo "FPP HomeKit plugin installation complete."
