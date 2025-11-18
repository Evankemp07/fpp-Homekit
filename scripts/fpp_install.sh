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
UPDATED_USING_SCRIPT=0
if [ ! -f "${SCRIPTS_DIR}/homekit_config.json" ] && [ ! -f "${SCRIPTS_DIR}/homekit_accessory.state" ]; then
    FIRST_INSTALL=1
    echo "Detected first-time installation (no config/state files found)." | tee -a "${INSTALL_LOG}"
else
    echo "Detected existing installation - using update script." | tee -a "${INSTALL_LOG}"

    # For updates, use the update script instead of reinstalling everything
    if [ -f "${SCRIPTS_DIR}/update_plugin.sh" ]; then
        # Stop the HomeKit service before updating (if possible)
        if [ -x "${PLUGIN_DIR}/scripts/postStop.sh" ]; then
            echo "Stopping HomeKit service before update..." | tee -a "${INSTALL_LOG}"
            if command -v sudo >/dev/null 2>&1; then
                sudo -u fpp MEDIADIR="${MEDIADIR}" bash "${PLUGIN_DIR}/scripts/postStop.sh" >> "${INSTALL_LOG}" 2>&1 || true
            else
                MEDIADIR="${MEDIADIR}" bash "${PLUGIN_DIR}/scripts/postStop.sh" >> "${INSTALL_LOG}" 2>&1 || true
            fi
        fi

        echo "Running update script..." | tee -a "${INSTALL_LOG}"
        cd "${PLUGIN_DIR}"
        bash "${SCRIPTS_DIR}/update_plugin.sh" 2>&1 | tee -a "${INSTALL_LOG}"
        UPDATED_USING_SCRIPT=1
        echo "Update completed successfully." | tee -a "${INSTALL_LOG}"
        cd "${PLUGIN_DIR}"
    else
        echo "Update script not found, falling back to normal install process." | tee -a "${INSTALL_LOG}"
    fi
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

    # Ensure pip is available - try multiple methods
    echo ""
    echo "Step 3: Preparing pip..."
    echo "Ensuring pip is installed via $PYTHON3 -m pip..." | tee -a "${INSTALL_LOG}"
    PIP3=""
    
    # Method 1: Try python3 -m pip (preferred)
    echo "Checking: $PYTHON3 -m pip --version" | tee -a "${INSTALL_LOG}"
    if $PYTHON3 -m pip --version >> "${INSTALL_LOG}" 2>&1; then
        PIP3="$PYTHON3 -m pip"
        echo "✓ Found pip via: $PYTHON3 -m pip" | tee -a "${INSTALL_LOG}"
    # Method 2: Try pip3 command directly
    elif command -v pip3 >/dev/null 2>&1; then
        PIP3="pip3"
        echo "✓ Found pip via: pip3 command" | tee -a "${INSTALL_LOG}"
        # Verify it works with the same Python
        if pip3 --version >> "${INSTALL_LOG}" 2>&1; then
            echo "✓ Verified pip3 works" | tee -a "${INSTALL_LOG}"
        fi
    # Method 3: Try to bootstrap pip with ensurepip
    else
        echo "Attempting to bootstrap pip with ensurepip..." | tee -a "${INSTALL_LOG}"
        if $PYTHON3 -m ensurepip --upgrade >> "${INSTALL_LOG}" 2>&1; then
            echo "ensurepip completed, checking pip..." | tee -a "${INSTALL_LOG}"
            if $PYTHON3 -m pip --version >> "${INSTALL_LOG}" 2>&1; then
                PIP3="$PYTHON3 -m pip"
                echo "✓ Successfully bootstrapped pip via ensurepip" | tee -a "${INSTALL_LOG}"
            else
                echo "Warning: ensurepip completed but pip still not available" | tee -a "${INSTALL_LOG}"
            fi
        else
            echo "Warning: $PYTHON3 -m ensurepip failed or not available" | tee -a "${INSTALL_LOG}"
        fi
    fi
    
    # Method 4: Try to install python3-pip automatically (if we have root/sudo access)
    if [ -z "$PIP3" ]; then
        echo "pip not found, attempting to install python3-pip..." | tee -a "${INSTALL_LOG}"
        
        # Check if we can use apt-get (Debian/Ubuntu/Raspberry Pi OS)
        if command -v apt-get >/dev/null 2>&1; then
            INSTALL_CMD=""
            # Check if we're running as root
            if [ "$(id -u)" -eq 0 ]; then
                INSTALL_CMD="apt-get"
                echo "Running as root, will use apt-get directly..." | tee -a "${INSTALL_LOG}"
            # Try with sudo if available (passwordless sudo)
            elif command -v sudo >/dev/null 2>&1 && sudo -n true 2>/dev/null; then
                INSTALL_CMD="sudo apt-get"
                echo "Passwordless sudo available, will use sudo apt-get..." | tee -a "${INSTALL_LOG}"
            fi
            
            if [ -n "$INSTALL_CMD" ]; then
                echo "Attempting to install python3-pip..." | tee -a "${INSTALL_LOG}"
                if $INSTALL_CMD update -qq >> "${INSTALL_LOG}" 2>&1 && \
                   $INSTALL_CMD install -y python3-pip >> "${INSTALL_LOG}" 2>&1; then
                    echo "✓ Successfully installed python3-pip" | tee -a "${INSTALL_LOG}"
                    sleep 2  # Give system a moment to register the new package
                    # Now try to find pip again
                    if $PYTHON3 -m pip --version >> "${INSTALL_LOG}" 2>&1; then
                        PIP3="$PYTHON3 -m pip"
                        echo "✓ Found pip after installation: $PYTHON3 -m pip" | tee -a "${INSTALL_LOG}"
                    elif command -v pip3 >/dev/null 2>&1; then
                        PIP3="pip3"
                        echo "✓ Found pip3 after installation" | tee -a "${INSTALL_LOG}"
                    fi
                else
                    echo "Warning: Could not install python3-pip automatically" | tee -a "${INSTALL_LOG}"
                    echo "Check ${INSTALL_LOG} for details" | tee -a "${INSTALL_LOG}"
                fi
            else
                echo "Warning: Cannot auto-install python3-pip (need root or passwordless sudo)" | tee -a "${INSTALL_LOG}"
            fi
        fi
    fi
    
    # If still no pip, give helpful error
    if [ -z "$PIP3" ]; then
        echo "" | tee -a "${INSTALL_LOG}"
        echo "ERROR: $PYTHON3 -m pip is not available. Please install python3-pip." | tee -a "${INSTALL_LOG}"
        echo "" | tee -a "${INSTALL_LOG}"
        echo "On Debian/Ubuntu/Raspberry Pi OS:" | tee -a "${INSTALL_LOG}"
        echo "  sudo apt-get update && sudo apt-get install python3-pip" | tee -a "${INSTALL_LOG}"
        echo "" | tee -a "${INSTALL_LOG}"
        echo "On macOS:" | tee -a "${INSTALL_LOG}"
        echo "  $PYTHON3 -m ensurepip --upgrade" | tee -a "${INSTALL_LOG}"
        echo "  or: brew install python3" | tee -a "${INSTALL_LOG}"
        echo "" | tee -a "${INSTALL_LOG}"
        echo "After installing python3-pip, restart the plugin installation." | tee -a "${INSTALL_LOG}"
        exit 1
    fi
    
    echo "Using pip command: $PIP3" | tee -a "${INSTALL_LOG}"
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

    if [ "${UPDATED_USING_SCRIPT}" -eq 1 ]; then
        echo "Ensuring Python dependencies after update..." | tee -a "${INSTALL_LOG}"
        if [ -n "$PYTHON3" ]; then
            "${SCRIPTS_DIR}/install_python_deps.sh" "${PLUGIN_DIR}" "$PYTHON3" >> "${INSTALL_LOG}" 2>&1 || \
                echo "Warning: Could not verify Python dependencies" | tee -a "${INSTALL_LOG}"
        fi
    fi
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

# Ensure helper scripts are executable
if [ -f "${PLUGIN_DIR}/scripts/check_mqtt_status.py" ]; then
    chmod +x "${PLUGIN_DIR}/scripts/check_mqtt_status.py"
    echo "✓ Made check_mqtt_status.py executable"
fi

if [ -f "${PLUGIN_DIR}/scripts/update_plugin.sh" ]; then
    chmod +x "${PLUGIN_DIR}/scripts/update_plugin.sh"
    echo "✓ Made update_plugin.sh executable"
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

# Check and start mosquitto for MQTT
echo ""
echo "Step 6a: Checking MQTT broker (mosquitto)..."
if command -v systemctl >/dev/null 2>&1; then
    if systemctl is-active --quiet mosquitto 2>/dev/null; then
        echo "✓ mosquitto is running"
    else
        echo "mosquitto is not running, attempting to start it..."
        # Try to start mosquitto (may require sudo, but plugin manager usually runs with privileges)
        if systemctl start mosquitto 2>/dev/null; then
            echo "✓ Started mosquitto"
            # Enable it to start on boot
            systemctl enable mosquitto 2>/dev/null || true
        elif sudo systemctl start mosquitto 2>/dev/null; then
            echo "✓ Started mosquitto (with sudo)"
            sudo systemctl enable mosquitto 2>/dev/null || true
        else
            echo "WARNING: Could not start mosquitto. MQTT control will not work."
            echo "You may need to manually run: sudo systemctl start mosquitto"
            echo "Or check if mosquitto is installed: sudo apt-get install mosquitto"
        fi
    fi
    
    # Verify it's listening on the expected port
    if command -v netstat >/dev/null 2>&1; then
        if netstat -tuln 2>/dev/null | grep -q ":1883 "; then
            echo "✓ mosquitto listening on port 1883"
        fi
    elif command -v ss >/dev/null 2>&1; then
        if ss -tuln 2>/dev/null | grep -q ":1883 "; then
            echo "✓ mosquitto listening on port 1883"
        fi
    fi
fi

# Restart the HomeKit service after install/update
if [ $FIRST_INSTALL -eq 1 ] || [ "${UPDATED_USING_SCRIPT}" -eq 1 ]; then
    echo ""
    echo "Step 6b: Starting HomeKit service..."
    if [ -x "${PLUGIN_DIR}/scripts/postStart.sh" ]; then
        if command -v sudo >/dev/null 2>&1; then
            sudo -u fpp MEDIADIR="${MEDIADIR}" bash "${PLUGIN_DIR}/scripts/postStart.sh" >> "${INSTALL_LOG}" 2>&1 || \
                echo "Warning: Could not start HomeKit service automatically" | tee -a "${INSTALL_LOG}"
        else
            MEDIADIR="${MEDIADIR}" bash "${PLUGIN_DIR}/scripts/postStart.sh" >> "${INSTALL_LOG}" 2>&1 || \
                echo "Warning: Could not start HomeKit service automatically" | tee -a "${INSTALL_LOG}"
        fi
    fi
fi

# Update pluginInfo.json SHA after update (so FPP knows plugin is up to date)
if [ $FIRST_INSTALL -eq 0 ]; then
    echo ""
    echo "Step 7: Updating plugin SHA..."
    PLUGIN_INFO_FILE="${PLUGIN_DIR}/pluginInfo.json"
    
    CURRENT_SHA=""
    CURRENT_BRANCH="master"
    
    # Try multiple methods to get the SHA
    # Method 1: If this is a git repository, get SHA from git
    if [ -d "${PLUGIN_DIR}/.git" ] && command -v git >/dev/null 2>&1; then
        cd "${PLUGIN_DIR}" || exit 1
        
        # Get the branch name
        CURRENT_BRANCH=$(git rev-parse --abbrev-ref HEAD 2>/dev/null || echo "master")
        
        # Fetch latest from remote to ensure we have current refs (quiet, no output)
        git fetch origin "${CURRENT_BRANCH}" >/dev/null 2>&1 || true
        
        # Try to get SHA from remote branch first (what FPP checks against)
        REMOTE_SHA=""
        if git rev-parse --verify "origin/${CURRENT_BRANCH}" >/dev/null 2>&1; then
            REMOTE_SHA=$(git rev-parse "origin/${CURRENT_BRANCH}" 2>/dev/null)
        fi
        
        # Use remote SHA if available, otherwise use local HEAD (should match after pull)
        CURRENT_SHA="${REMOTE_SHA:-$(git rev-parse HEAD 2>/dev/null)}"
        
        if [ -n "${REMOTE_SHA}" ]; then
            echo "Using remote SHA: ${REMOTE_SHA}" | tee -a "${INSTALL_LOG}"
        else
            echo "Using local HEAD SHA: ${CURRENT_SHA}" | tee -a "${INSTALL_LOG}"
        fi
    fi
    
    # Method 2: If no git repo, try to get SHA from FPP's plugin update mechanism
    # FPP stores the remote SHA somewhere - check if we can find it
    if [ -z "${CURRENT_SHA}" ]; then
        # Check if FPP has stored the remote SHA somewhere
        # FPP might pass it as an environment variable or store it
        if [ -n "${FPP_PLUGIN_SHA}" ]; then
            CURRENT_SHA="${FPP_PLUGIN_SHA}"
            echo "Using SHA from FPP environment: ${CURRENT_SHA}" | tee -a "${INSTALL_LOG}"
        fi
    fi
    
    # Method 3: Try to get SHA from the srcURL by querying GitHub API
    if [ -z "${CURRENT_SHA}" ] && [ -f "${PLUGIN_INFO_FILE}" ] && command -v curl >/dev/null 2>&1; then
        # Extract repo info from pluginInfo.json
        SRC_URL=$(grep -o '"srcURL":\s*"[^"]*"' "${PLUGIN_INFO_FILE}" | cut -d'"' -f4)
        if [ -n "${SRC_URL}" ]; then
            # Convert git URL to API URL (e.g., https://github.com/user/repo.git -> https://api.github.com/repos/user/repo/commits/master)
            if echo "${SRC_URL}" | grep -q "github.com"; then
                REPO_PATH=$(echo "${SRC_URL}" | sed 's/.*github.com[:/]\([^.]*\)\.git/\1/' | sed 's/.*github.com\/\([^.]*\)\.git/\1/')
                BRANCH_NAME=$(grep -o '"branch":\s*"[^"]*"' "${PLUGIN_INFO_FILE}" | cut -d'"' -f4 || echo "master")
                API_URL="https://api.github.com/repos/${REPO_PATH}/commits/${BRANCH_NAME}"
                
                echo "Attempting to fetch SHA from GitHub API: ${API_URL}" | tee -a "${INSTALL_LOG}"
                SHA_RESPONSE=$(curl -s "${API_URL}" 2>/dev/null)
                if [ $? -eq 0 ] && [ -n "${SHA_RESPONSE}" ]; then
                    API_SHA=$(echo "${SHA_RESPONSE}" | grep -o '"sha":\s*"[^"]*"' | head -1 | cut -d'"' -f4)
                    if [ -n "${API_SHA}" ]; then
                        CURRENT_SHA="${API_SHA}"
                        CURRENT_BRANCH="${BRANCH_NAME}"
                        echo "Got SHA from GitHub API: ${CURRENT_SHA}" | tee -a "${INSTALL_LOG}"
                    fi
                fi
            fi
        fi
    fi
    
    if [ -n "${CURRENT_SHA}" ] && [ -f "${PLUGIN_INFO_FILE}" ]; then
        # Update SHA in pluginInfo.json using Python (more reliable than sed for JSON)
        if command -v python3 >/dev/null 2>&1; then
            python3 << EOF
import json
import sys
import os

try:
    plugin_info_file = '${PLUGIN_INFO_FILE}'
    current_sha = '${CURRENT_SHA}'
    current_branch = '${CURRENT_BRANCH}'
    
    # Verify file exists
    if not os.path.exists(plugin_info_file):
        print(f"Error: pluginInfo.json not found at {plugin_info_file}")
        sys.exit(1)
    
    # Read existing file
    with open(plugin_info_file, 'r') as f:
        data = json.load(f)
    
    # Update SHA in first version entry
    if 'versions' in data and len(data['versions']) > 0:
        old_sha = data['versions'][0].get('sha', '')
        data['versions'][0]['sha'] = current_sha
        if current_branch and current_branch.strip():
            data['versions'][0]['branch'] = current_branch
        
        # Write back to file
        with open(plugin_info_file, 'w') as f:
            json.dump(data, f, indent='\t')
        
        if old_sha != current_sha:
            print(f"✓ Updated plugin SHA from {old_sha[:8] if old_sha else 'empty'} to {current_sha[:8]}")
        else:
            print(f"✓ Plugin SHA already up to date: {current_sha[:8]}")
        sys.exit(0)
    else:
        print("Warning: No versions array found in pluginInfo.json")
        sys.exit(1)
except Exception as e:
    import traceback
    print(f"Error: Could not update plugin SHA: {e}")
    traceback.print_exc()
    sys.exit(1)
EOF
            SHA_UPDATE_RESULT=$?
            if [ $SHA_UPDATE_RESULT -eq 0 ]; then
                echo "Plugin SHA updated successfully" >> "${INSTALL_LOG}"
            else
                echo "ERROR: Failed to update plugin SHA (exit code: $SHA_UPDATE_RESULT)" | tee -a "${INSTALL_LOG}"
            fi
        else
            echo "Warning: python3 not available, cannot update SHA automatically" | tee -a "${INSTALL_LOG}"
        fi
    else
        echo "Warning: Could not determine git SHA or pluginInfo.json not found" | tee -a "${INSTALL_LOG}"
        echo "  CURRENT_SHA: ${CURRENT_SHA}" | tee -a "${INSTALL_LOG}"
        echo "  PLUGIN_INFO_FILE: ${PLUGIN_INFO_FILE}" | tee -a "${INSTALL_LOG}"
        if [ -f "${PLUGIN_INFO_FILE}" ]; then
            echo "  File exists: YES" | tee -a "${INSTALL_LOG}"
        else
            echo "  File exists: NO" | tee -a "${INSTALL_LOG}"
        fi
    fi
    
    # Restart service if it was running
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

echo ""
echo "made with ❤️ in PA"
