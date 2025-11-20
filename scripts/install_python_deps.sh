#!/bin/bash
#
# install_python_deps.sh
# Installs Python dependencies required by the FPP HomeKit plugin
#
# Usage: install_python_deps.sh <plugin_dir> <python3_executable> [pip_command]
#
# Arguments:
#   plugin_dir:        Full path to the plugin directory
#   python3_executable: Path to Python 3 executable
#   pip_command:       Optional pip command (defaults to python3 -m pip)
#
# This script installs dependencies from requirements.txt using pip.
# If a venv exists, it uses the venv's pip. Otherwise, it uses the provided Python/pip.

PLUGIN_DIR="${1}"
PYTHON3="${2}"
# Allow pip command to be overridden, default to python3 -m pip
PIP_CMD="${3:-$PYTHON3 -m pip}"
REQUIREMENTS_FILE="${PLUGIN_DIR}/scripts/requirements.txt"
INSTALL_LOG="${PLUGIN_DIR}/scripts/install.log"
VENV_DIR="${PLUGIN_DIR}/venv"

# Always use venv - create it if it doesn't exist
# This ensures we never install to system Python
if [ -d "${VENV_DIR}" ] && [ -x "${VENV_DIR}/bin/python3" ]; then
    PYTHON3="${VENV_DIR}/bin/python3"
    PIP_CMD="${VENV_DIR}/bin/pip"
    echo "Using existing virtual environment: ${VENV_DIR}" | tee -a "${INSTALL_LOG}"
else
    # Venv doesn't exist or is incomplete, create it
    echo "Virtual environment not found or incomplete, creating it..." | tee -a "${INSTALL_LOG}"
    echo "This may take 30-60 seconds..." | tee -a "${INSTALL_LOG}"
    if [ -f "${PLUGIN_DIR}/scripts/install_venv.sh" ]; then
        # Show progress by using tee instead of redirecting all output
        if bash "${PLUGIN_DIR}/scripts/install_venv.sh" 2>&1 | tee -a "${INSTALL_LOG}"; then
            if [ -d "${VENV_DIR}" ] && [ -x "${VENV_DIR}/bin/python3" ]; then
                PYTHON3="${VENV_DIR}/bin/python3"
                PIP_CMD="${VENV_DIR}/bin/pip"
                echo "✓ Virtual environment created successfully" | tee -a "${INSTALL_LOG}"
            else
                echo "ERROR: Venv creation reported success but venv not found at ${VENV_DIR}" | tee -a "${INSTALL_LOG}"
                exit 1
            fi
        else
            echo "ERROR: Failed to create virtual environment. Check ${INSTALL_LOG} for details." | tee -a "${INSTALL_LOG}"
            exit 1
        fi
    else
        # Fallback: create venv directly if install_venv.sh doesn't exist
        echo "install_venv.sh not found, creating venv directly..." | tee -a "${INSTALL_LOG}"
        echo "Creating virtual environment (this may take 30-60 seconds)..." | tee -a "${INSTALL_LOG}"
        if $PYTHON3 -m venv "${VENV_DIR}" 2>&1 | tee -a "${INSTALL_LOG}"; then
            if [ -d "${VENV_DIR}" ] && [ -x "${VENV_DIR}/bin/python3" ]; then
                PYTHON3="${VENV_DIR}/bin/python3"
                PIP_CMD="${VENV_DIR}/bin/pip"
                echo "✓ Virtual environment created successfully" | tee -a "${INSTALL_LOG}"
            else
                echo "ERROR: Failed to create virtual environment" | tee -a "${INSTALL_LOG}"
                exit 1
            fi
        else
            echo "ERROR: Failed to create virtual environment with python3 -m venv" | tee -a "${INSTALL_LOG}"
            exit 1
        fi
    fi
fi

# Timeout settings (in seconds)
PIP_TIMEOUT="${PIP_TIMEOUT:-600}"  # 10 minutes default timeout for pip operations

if [ -z "$PLUGIN_DIR" ] || [ -z "$PYTHON3" ]; then
    echo "ERROR: Missing required parameters for install_python_deps.sh"
    exit 1
fi

if [ ! -f "${REQUIREMENTS_FILE}" ]; then
    echo "ERROR: requirements.txt not found at ${REQUIREMENTS_FILE}"
    exit 1
fi

# Immediate feedback that script is running
echo "Starting Python dependency installation..." | tee -a "${INSTALL_LOG}"
echo "Checking system requirements..." | tee -a "${INSTALL_LOG}"

# Check if timeout command is available
TIMEOUT_CMD=""
if command -v timeout >/dev/null 2>&1; then
    TIMEOUT_CMD="timeout"
elif command -v gtimeout >/dev/null 2>&1; then
    TIMEOUT_CMD="gtimeout"
fi

# Helper function to run pip commands with timeout and progress indication
# Usage: run_pip_with_timeout <timeout_seconds> <description> <pip_command_and_args...>
run_pip_with_timeout() {
    local timeout_sec="$1"
    local description="$2"
    shift 2
    local pip_args=("$@")
    
    echo "[$(date '+%H:%M:%S')] Starting: $description" | tee -a "${INSTALL_LOG}"
    
    # Use timeout if available, otherwise run directly
    if [ -n "$TIMEOUT_CMD" ]; then
        # Build command string with proper quoting for bash -c
        # This handles PIP_CMD which might be "python3 -m pip" or just "pip3"
        # Split PIP_CMD if it contains spaces and quote each part
        local cmd_str=""
        if echo "$PIP_CMD" | grep -q " "; then
            # PIP_CMD has spaces, split and quote each part
            for part in $PIP_CMD; do
                if [ -z "$cmd_str" ]; then
                    cmd_str="$(printf '%q' "$part")"
                else
                    cmd_str="$cmd_str $(printf '%q' "$part")"
                fi
            done
        else
            # Single command, quote it
            cmd_str="$(printf '%q' "$PIP_CMD")"
        fi
        # Add pip arguments
        for arg in "${pip_args[@]}"; do
            cmd_str="$cmd_str $(printf '%q' "$arg")"
        done
        
        # Execute with timeout using bash -c to properly handle command with spaces
        # Filter out verbose "Removing" messages from pip output, but preserve exit code
        local pip_output
        pip_output=$($TIMEOUT_CMD "$timeout_sec" bash -c "$cmd_str" 2>&1 | grep -v "^Removing ")
        local exit_code=$?
        
        # Append filtered output to log (already filtered above)
        echo "$pip_output" >> "${INSTALL_LOG}"
        
        if [ $exit_code -eq 0 ]; then
            echo "[$(date '+%H:%M:%S')] ✓ Completed: $description" | tee -a "${INSTALL_LOG}"
            return 0
        else
            if [ $exit_code -eq 124 ]; then
                echo "[$(date '+%H:%M:%S')] ✗ TIMEOUT: $description (exceeded ${timeout_sec}s)" | tee -a "${INSTALL_LOG}"
            else
                echo "[$(date '+%H:%M:%S')] ✗ FAILED: $description (exit code: $exit_code)" | tee -a "${INSTALL_LOG}"
            fi
            return $exit_code
        fi
    else
        # No timeout command available, run directly but with progress indication
        echo "Warning: timeout command not available, running without timeout protection" | tee -a "${INSTALL_LOG}"
        # Build command string with proper quoting (same as timeout case)
        # Split PIP_CMD if it contains spaces and quote each part
        local cmd_str=""
        if echo "$PIP_CMD" | grep -q " "; then
            # PIP_CMD has spaces, split and quote each part
            for part in $PIP_CMD; do
                if [ -z "$cmd_str" ]; then
                    cmd_str="$(printf '%q' "$part")"
                else
                    cmd_str="$cmd_str $(printf '%q' "$part")"
                fi
            done
        else
            # Single command, quote it
            cmd_str="$(printf '%q' "$PIP_CMD")"
        fi
        # Add pip arguments
        for arg in "${pip_args[@]}"; do
            cmd_str="$cmd_str $(printf '%q' "$arg")"
        done
        # Execute using bash -c to properly handle command with spaces
        # Filter out verbose "Removing" messages from pip output, but preserve exit code
        local pip_output
        pip_output=$(bash -c "$cmd_str" 2>&1 | grep -v "^Removing ")
        local exit_code=$?
        
        # Append filtered output to log (already filtered above)
        echo "$pip_output" >> "${INSTALL_LOG}"
        
        if [ $exit_code -eq 0 ]; then
            echo "[$(date '+%H:%M:%S')] ✓ Completed: $description" | tee -a "${INSTALL_LOG}"
            return 0
        else
            echo "[$(date '+%H:%M:%S')] ✗ FAILED: $description" | tee -a "${INSTALL_LOG}"
            return $exit_code
        fi
    fi
}

echo ""
echo "Installing Python dependencies from requirements.txt..."
echo "This may take a few minutes..."
echo "Detailed log: ${INSTALL_LOG}"
echo "Timeout: ${PIP_TIMEOUT}s per operation"
echo "Note: If installation appears stuck, check ${INSTALL_LOG} for progress"
echo ""

echo "Using pip command: $PIP_CMD" | tee -a "${INSTALL_LOG}"
PIP_VERSION=$($PIP_CMD --version 2>&1 || echo "unknown")
echo "pip version: $PIP_VERSION" | tee -a "${INSTALL_LOG}"

# Attempt to upgrade pip to latest version before installing dependencies
# This helps ensure compatibility with the latest package formats
# In venv, no special flags needed
echo "Upgrading pip in venv (best effort)..." | tee -a "${INSTALL_LOG}"
if ! run_pip_with_timeout 300 "pip upgrade" install --upgrade pip --no-input --disable-pip-version-check --quiet; then
    echo "Warning: Could not upgrade pip, continuing with existing version..." | tee -a "${INSTALL_LOG}"
fi

# Helper function to install dependencies with specified pip flags
# Args: Space-separated string of pip install flags (e.g., "--user --upgrade")
install_with() {
    local args=()
    # Convert space-separated flags string into array for proper argument passing
    if [ -n "$1" ]; then
        # shellcheck disable=SC2206
        args=($1)
    fi
    
    # Add non-interactive flags to prevent hanging and suppress verbose output
    # Use --quiet to suppress most output, but also filter out "Removing" messages
    args+=("--no-input" "--disable-pip-version-check" "--quiet" "--no-warn-script-location")

    if run_pip_with_timeout "$PIP_TIMEOUT" "dependency installation (${1:-default flags})" install -r "${REQUIREMENTS_FILE}" "${args[@]}"; then
        echo "✓ Dependencies installed successfully (${1:-default flags})" | tee -a "${INSTALL_LOG}"
        return 0
    fi

    return 1
}

INSTALL_SUCCESS=0

# Install dependencies in venv (we always use venv now)
echo "Removing old venv packages..." | tee -a "${INSTALL_LOG}"
echo "Installing dependencies in venv..." | tee -a "${INSTALL_LOG}"
if install_with "--upgrade"; then
    INSTALL_SUCCESS=1
fi

if [ $INSTALL_SUCCESS -eq 0 ]; then
    echo "ERROR: Failed to install Python dependencies in venv." | tee -a "${INSTALL_LOG}"
    echo "Last 30 lines of error output:" | tee -a "${INSTALL_LOG}"
    tail -30 "${INSTALL_LOG}" || true
    echo ""
    echo "Try manual install with:" | tee -a "${INSTALL_LOG}"
    echo "  ${VENV_DIR}/bin/pip install -r ${REQUIREMENTS_FILE} --upgrade"
    exit 1
fi

# Verify that all critical dependencies are importable
# Use the same Python executable that will run the service to ensure compatibility
echo ""
echo "Verifying installed dependencies with $PYTHON3..." | tee -a "${INSTALL_LOG}"
MISSING_DEPS=0

# Determine Python site-packages locations for debugging purposes
# This helps identify where packages were installed if verification fails
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
    echo "    Attempting to install qrcode..." | tee -a "${INSTALL_LOG}"
    if run_pip_with_timeout 300 "qrcode installation" install --upgrade "qrcode[pil]>=7.0.0" --no-input --disable-pip-version-check --quiet; then
        echo "  ✓ qrcode installed successfully" | tee -a "${INSTALL_LOG}"
    else
        MISSING_DEPS=1
    fi
else
    echo "  ✓ qrcode installed" | tee -a "${INSTALL_LOG}"
fi

if ! $PYTHON3 -c "from PIL import Image" 2>>"${INSTALL_LOG}"; then
    echo "  ✗ PIL/Pillow not found (required for QR code generation)" | tee -a "${INSTALL_LOG}"
    echo "    Attempting to install Pillow..." | tee -a "${INSTALL_LOG}"
    if run_pip_with_timeout 300 "Pillow installation" install --upgrade "Pillow>=9.0.0" --no-input --disable-pip-version-check --quiet; then
        echo "  ✓ PIL/Pillow installed successfully" | tee -a "${INSTALL_LOG}"
    else
        MISSING_DEPS=1
    fi
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

