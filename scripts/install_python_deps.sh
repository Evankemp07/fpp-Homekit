#!/bin/bash

# Install Python dependencies for fpp-Homekit plugin
# This script is called from fpp_install.sh

PLUGIN_DIR="${1}"
PYTHON3="${2}"
PIP3="${3}"
REQUIREMENTS_FILE="${PLUGIN_DIR}/scripts/requirements.txt"
INSTALL_LOG="${PLUGIN_DIR}/scripts/install.log"

if [ -z "$PLUGIN_DIR" ] || [ -z "$PYTHON3" ] || [ -z "$PIP3" ]; then
    echo "ERROR: Missing required parameters for install_python_deps.sh"
    exit 1
fi

if [ ! -f "${REQUIREMENTS_FILE}" ]; then
    echo "ERROR: requirements.txt not found at ${REQUIREMENTS_FILE}"
    exit 1
fi

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

exit 0

