#!/bin/bash

# fpp-Homekit install script

# Include common scripts functions and variables
. ${FPPDIR}/scripts/common

PLUGIN_DIR="${MEDIADIR}/plugins/fpp-Homekit"
REQUIREMENTS_FILE="${PLUGIN_DIR}/scripts/requirements.txt"
INSTALL_LOG="${PLUGIN_DIR}/scripts/install.log"

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
        echo "✓ All required system packages are installed"
    fi
fi

# Find python3 (must match the one used at runtime)
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
PYTHON_PATH=$($PYTHON3 -c "import sys; print(sys.executable)" 2>/dev/null)
echo "Python executable: $PYTHON_PATH"

# Find pip3 (prefer pip3 associated with python3)
PIP3=""
PIP_CHECK_OUTPUT=""

# Try python3 -m pip first (most reliable)
if $PYTHON3 -m pip --version >/dev/null 2>&1; then
    PIP3="$PYTHON3 -m pip"
    PIP_CHECK_OUTPUT=$($PYTHON3 -m pip --version 2>&1)
elif $PYTHON3 -c "import pip" >/dev/null 2>&1; then
    # pip module exists but might not work as -m pip, try direct import
    PIP3="$PYTHON3 -m pip"
    PIP_CHECK_OUTPUT=$($PYTHON3 -m pip --version 2>&1)
elif command -v pip3 >/dev/null 2>&1; then
    PIP3="pip3"
    PIP_CHECK_OUTPUT=$(pip3 --version 2>&1)
elif command -v pip >/dev/null 2>&1; then
    PIP3="pip"
    PIP_CHECK_OUTPUT=$(pip --version 2>&1)
fi

if [ -z "$PIP3" ]; then
    echo "ERROR: pip3 not found."
    echo ""
    echo "Attempting to bootstrap pip using ensurepip..."
    
    # Try ensurepip and capture output
    ENSUREPIP_OUTPUT=$($PYTHON3 -m ensurepip --upgrade 2>&1)
    ENSUREPIP_RESULT=$?
    
    if [ $ENSUREPIP_RESULT -eq 0 ]; then
        echo "✓ Successfully bootstrapped pip"
        # Try again after bootstrapping
        if $PYTHON3 -m pip --version >/dev/null 2>&1; then
            PIP3="$PYTHON3 -m pip"
            PIP_CHECK_OUTPUT=$($PYTHON3 -m pip --version 2>&1)
        fi
    else
        echo "ensurepip failed (exit code: $ENSUREPIP_RESULT)"
        echo "Output: $ENSUREPIP_OUTPUT"
        echo ""
        echo "Trying alternative: downloading get-pip.py..."
        
        # Try downloading get-pip.py as fallback
        GET_PIP_URL="https://bootstrap.pypa.io/get-pip.py"
        GET_PIP_SCRIPT="/tmp/get-pip.py"
        
        if command -v curl >/dev/null 2>&1; then
            curl -sSL "$GET_PIP_URL" -o "$GET_PIP_SCRIPT" 2>&1
        elif command -v wget >/dev/null 2>&1; then
            wget -q -O "$GET_PIP_SCRIPT" "$GET_PIP_URL" 2>&1
        fi
        
        if [ -f "$GET_PIP_SCRIPT" ]; then
            echo "Running get-pip.py with --user flag..."
            GETPIP_OUTPUT=$($PYTHON3 "$GET_PIP_SCRIPT" --user 2>&1)
            GETPIP_RESULT=$?
            
            if [ $GETPIP_RESULT -eq 0 ]; then
                echo "✓ Successfully installed pip using get-pip.py"
                # Try again after installing
                if $PYTHON3 -m pip --version >/dev/null 2>&1; then
                    PIP3="$PYTHON3 -m pip"
                    PIP_CHECK_OUTPUT=$($PYTHON3 -m pip --version 2>&1)
                fi
            else
                echo "get-pip.py with --user failed (exit code: $GETPIP_RESULT)"
                echo "This is likely due to PEP 668 (externally-managed-environment)"
                echo ""
                echo "Trying with --break-system-packages flag (use with caution)..."
                GETPIP_OUTPUT=$($PYTHON3 "$GET_PIP_SCRIPT" --user --break-system-packages 2>&1)
                GETPIP_RESULT=$?
                
                if [ $GETPIP_RESULT -eq 0 ]; then
                    echo "✓ Successfully installed pip using get-pip.py"
                    # Try again after installing
                    if $PYTHON3 -m pip --version >/dev/null 2>&1; then
                        PIP3="$PYTHON3 -m pip"
                        PIP_CHECK_OUTPUT=$($PYTHON3 -m pip --version 2>&1)
                    fi
                else
                    echo "get-pip.py failed even with --break-system-packages"
                    echo "Output: $GETPIP_OUTPUT"
                fi
            fi
            rm -f "$GET_PIP_SCRIPT"
        else
            echo "Could not download get-pip.py (curl/wget not available)"
        fi
    fi
    
    if [ -z "$PIP3" ]; then
        echo ""
        echo "ERROR: pip is still not available after attempting to bootstrap."
        echo ""
        echo "This system uses Debian/Ubuntu's externally-managed Python environment (PEP 668),"
        echo "which prevents installing pip without the system package."
        echo ""
        echo "The python3-pip package MUST be installed via the system package manager."
        echo "This should happen automatically via pluginInfo.json dependencies during plugin"
        echo "installation, but if it didn't, you need to install it manually:"
        echo ""
        echo "  sudo apt-get update"
        echo "  sudo apt-get install python3-pip"
        echo ""
        echo "After installing python3-pip, re-run the plugin installation."
        echo ""
        echo "Note: If python3-pip is listed in pluginInfo.json dependencies but wasn't"
        echo "installed automatically, this may be an FPP plugin installer issue. You can"
        echo "install the dependencies manually and then reinstall the plugin."
        exit 1
    fi
fi

echo "Found pip: $PIP3"
echo "$PIP_CHECK_OUTPUT"

# Create log file for detailed error output
echo "Installation log: ${INSTALL_LOG}" > "${INSTALL_LOG}"
echo "Started: $(date)" >> "${INSTALL_LOG}"
echo "Python: $PYTHON3 ($PYTHON_PATH)" >> "${INSTALL_LOG}"
echo "Pip: $PIP3" >> "${INSTALL_LOG}"

# Install Python dependencies
if [ -f "${REQUIREMENTS_FILE}" ]; then
    echo ""
    echo "Installing Python dependencies from requirements.txt..."
    echo "This may take a few minutes..."
    echo "Detailed log: ${INSTALL_LOG}"
    
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
            exit 1
        fi
    fi
    
    # Verify critical dependencies are installed using the EXACT Python that will run the service
    echo ""
    echo "Verifying installed dependencies with $PYTHON3..."
    MISSING_DEPS=0
    
    # Get Python's site-packages path to verify installation location
    PYTHON_SITE=$($PYTHON3 -c "import site; print('\\n'.join(site.getsitepackages() + [site.getusersitepackages()]))" 2>/dev/null)
    echo "Python site-packages locations:" | tee -a "${INSTALL_LOG}"
    echo "$PYTHON_SITE" | tee -a "${INSTALL_LOG}"
    
    if ! $PYTHON3 -c "import pyhap" 2>>"${INSTALL_LOG}"; then
        echo "  ✗ HAP-python (pyhap) not found" | tee -a "${INSTALL_LOG}"
        MISSING_DEPS=1
        # Try to find where it might be installed
        echo "  Searching for pyhap installation..." | tee -a "${INSTALL_LOG}"
        $PYTHON3 -c "import sys; print('\\n'.join(sys.path))" 2>>"${INSTALL_LOG}" | tee -a "${INSTALL_LOG}"
    else
        echo "  ✓ HAP-python installed" | tee -a "${INSTALL_LOG}"
        # Show version
        $PYTHON3 -c "import pyhap; print('  HAP-python version:', getattr(pyhap, '__version__', 'unknown'))" 2>/dev/null | tee -a "${INSTALL_LOG}"
    fi
    
    if ! $PYTHON3 -c "import requests" 2>>"${INSTALL_LOG}"; then
        echo "  ✗ requests not found" | tee -a "${INSTALL_LOG}"
        MISSING_DEPS=1
    else
        echo "  ✓ requests installed" | tee -a "${INSTALL_LOG}"
    fi
    
    if ! $PYTHON3 -c "import qrcode" 2>>"${INSTALL_LOG}"; then
        echo "  ✗ qrcode not found" | tee -a "${INSTALL_LOG}"
        MISSING_DEPS=1
    else
        echo "  ✓ qrcode installed" | tee -a "${INSTALL_LOG}"
    fi
    
    # Also verify PIL/Pillow (needed for qrcode[pil])
    if ! $PYTHON3 -c "from PIL import Image" 2>>"${INSTALL_LOG}"; then
        echo "  ✗ PIL/Pillow not found (needed for QR code generation)" | tee -a "${INSTALL_LOG}"
        MISSING_DEPS=1
    else
        echo "  ✓ PIL/Pillow installed" | tee -a "${INSTALL_LOG}"
    fi
    
    if [ $MISSING_DEPS -eq 1 ]; then
        echo ""
        echo "ERROR: Some dependencies are missing. The plugin will not work correctly." | tee -a "${INSTALL_LOG}"
        echo ""
        echo "Try installing manually with the exact Python that will run the service:"
        echo "  $PIP3 install -r ${REQUIREMENTS_FILE}"
        echo ""
        echo "Or check the installation log for details:"
        echo "  cat ${INSTALL_LOG}"
        exit 1
    else
        echo ""
        echo "✓ All Python dependencies verified successfully" | tee -a "${INSTALL_LOG}"
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
