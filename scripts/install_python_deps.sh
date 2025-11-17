#!/bin/bash

# Install Python dependencies for fpp-Homekit plugin
# Simplified workflow that always uses python -m pip

PLUGIN_DIR="${1}"
PYTHON3="${2}"
PIP_CMD="${3:-$PYTHON3 -m pip}"  # Allow pip command to be passed, default to python3 -m pip
REQUIREMENTS_FILE="${PLUGIN_DIR}/scripts/requirements.txt"
INSTALL_LOG="${PLUGIN_DIR}/scripts/install.log"

if [ -z "$PLUGIN_DIR" ] || [ -z "$PYTHON3" ]; then
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

echo "Using pip command: $PIP_CMD" | tee -a "${INSTALL_LOG}"
$PIP_CMD --version >> "${INSTALL_LOG}" 2>&1 || true

# Best-effort pip upgrade (try --user first, then system-wide)
echo "Upgrading pip (best effort)..." | tee -a "${INSTALL_LOG}"
if ! $PIP_CMD install --upgrade pip --user >> "${INSTALL_LOG}" 2>&1; then
    if ! $PIP_CMD install --upgrade pip >> "${INSTALL_LOG}" 2>&1; then
        echo "Warning: Could not upgrade pip, continuing..." | tee -a "${INSTALL_LOG}"
    fi
fi

install_with() {
    # Split the incoming string of extra args into an array (if provided)
    local args=()
    if [ -n "$1" ]; then
        # shellcheck disable=SC2206
        args=($1)
    fi

    if $PIP_CMD install -r "${REQUIREMENTS_FILE}" "${args[@]}" >> "${INSTALL_LOG}" 2>&1; then
        echo "✓ Dependencies installed (${1:-default flags})" | tee -a "${INSTALL_LOG}"
        return 0
    fi

    return 1
}

INSTALL_SUCCESS=0

# Try --user first (cleanest, doesn't break system package management)
echo "Trying installation with --user (recommended)..." | tee -a "${INSTALL_LOG}"
if install_with "--user --upgrade"; then
    INSTALL_SUCCESS=1
else
    # Fallback to system-wide if --user fails
    echo "Retrying installation system-wide..." | tee -a "${INSTALL_LOG}"
    if install_with "--upgrade"; then
        INSTALL_SUCCESS=1
    else
        # Last resort: --break-system-packages (only if everything else fails)
        echo "Attempting installation with --break-system-packages (last resort)..." | tee -a "${INSTALL_LOG}"
        if install_with "--upgrade --break-system-packages"; then
            INSTALL_SUCCESS=1
        fi
    fi
fi

if [ $INSTALL_SUCCESS -eq 0 ]; then
    echo "ERROR: Failed to install Python dependencies." | tee -a "${INSTALL_LOG}"
    echo "Last 30 lines of error output:" | tee -a "${INSTALL_LOG}"
    tail -30 "${INSTALL_LOG}" || true
    echo ""
    echo "Try manual install with one of:" | tee -a "${INSTALL_LOG}"
    echo "  $PIP_CMD install -r ${REQUIREMENTS_FILE} --user --upgrade"
    echo "  $PIP_CMD install -r ${REQUIREMENTS_FILE} --upgrade"
    echo "  $PIP_CMD install -r ${REQUIREMENTS_FILE} --upgrade --break-system-packages"
    exit 1
fi

# Verify critical dependencies using the same Python that will run the service
echo ""
echo "Verifying installed dependencies with $PYTHON3..." | tee -a "${INSTALL_LOG}"
MISSING_DEPS=0

PYTHON_SITE=$($PYTHON3 - <<'PY'
import site
paths = []
getsite = getattr(site, "getsitepackages", lambda: [])
paths.extend(getsite())
user_site = site.getusersitepackages()
if user_site:
    paths.append(user_site)
print("\n".join(paths))
PY
)
echo "Python site-packages locations:" | tee -a "${INSTALL_LOG}"
echo "$PYTHON_SITE" | tee -a "${INSTALL_LOG}"

if ! $PYTHON3 -c "import pyhap" 2>>"${INSTALL_LOG}"; then
    echo "  ✗ HAP-python (pyhap) not found" | tee -a "${INSTALL_LOG}"
    MISSING_DEPS=1
else
    echo "  ✓ HAP-python installed" | tee -a "${INSTALL_LOG}"
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

if ! $PYTHON3 -c "from PIL import Image" 2>>"${INSTALL_LOG}"; then
    echo "  ✗ PIL/Pillow not found (required for QR code generation)" | tee -a "${INSTALL_LOG}"
    MISSING_DEPS=1
else
    echo "  ✓ PIL/Pillow installed" | tee -a "${INSTALL_LOG}"
fi

if ! $PYTHON3 -c "import paho.mqtt.client" 2>>"${INSTALL_LOG}"; then
    echo "  ✗ paho-mqtt not found (required for MQTT control)" | tee -a "${INSTALL_LOG}"
    MISSING_DEPS=1
else
    echo "  ✓ paho-mqtt installed" | tee -a "${INSTALL_LOG}"
    $PYTHON3 -c "import paho.mqtt.client as mqtt; print('  paho-mqtt version:', getattr(mqtt, '__version__', 'unknown'))" 2>/dev/null | tee -a "${INSTALL_LOG}" || true
fi

if [ $MISSING_DEPS -eq 1 ]; then
    echo ""
    echo "ERROR: Some dependencies are missing. See ${INSTALL_LOG} for details." | tee -a "${INSTALL_LOG}"
    exit 1
fi

echo ""
echo "✓ All Python dependencies verified successfully" | tee -a "${INSTALL_LOG}"

exit 0

