#!/bin/bash

# fpp-Homekit install script
# Main installer that calls separate helper scripts

# Include common scripts functions and variables
. ${FPPDIR}/scripts/common

PLUGIN_DIR="${MEDIADIR}/plugins/fpp-Homekit"
SCRIPTS_DIR="${PLUGIN_DIR}/scripts"
INSTALL_LOG="${SCRIPTS_DIR}/install.log"

# Create log file
echo "Installation log: ${INSTALL_LOG}" > "${INSTALL_LOG}"
echo "Started: $(date)" >> "${INSTALL_LOG}"

# Determine if this is a brand new install (no config or state files)
FIRST_INSTALL=0
UPDATED_USING_SCRIPT=0
if [ ! -f "${SCRIPTS_DIR}/homekit_config.json" ] && [ ! -f "${SCRIPTS_DIR}/homekit_accessory.state" ]; then
    FIRST_INSTALL=1
    echo "Installing FPP HomeKit plugin..." | tee -a "${INSTALL_LOG}"
else
    echo "Updating FPP HomeKit plugin..." | tee -a "${INSTALL_LOG}"

    # For updates, use the update script instead of reinstalling everything
    if [ -f "${SCRIPTS_DIR}/update_plugin.sh" ]; then
        # Stop the HomeKit service before updating (if possible)
        if [ -x "${PLUGIN_DIR}/scripts/postStop.sh" ]; then
            if command -v sudo >/dev/null 2>&1; then
                sudo -u fpp MEDIADIR="${MEDIADIR}" bash "${PLUGIN_DIR}/scripts/postStop.sh" >> "${INSTALL_LOG}" 2>&1 || true
            else
                MEDIADIR="${MEDIADIR}" bash "${PLUGIN_DIR}/scripts/postStop.sh" >> "${INSTALL_LOG}" 2>&1 || true
            fi
        fi

        cd "${PLUGIN_DIR}"
        bash "${SCRIPTS_DIR}/update_plugin.sh" >> "${INSTALL_LOG}" 2>&1
        UPDATED_USING_SCRIPT=1
        echo "✓ Update complete" | tee -a "${INSTALL_LOG}"
        cd "${PLUGIN_DIR}"
    else
        echo "Update script not found, falling back to normal install process." >> "${INSTALL_LOG}"
    fi
fi

if [ $FIRST_INSTALL -eq 1 ]; then
    # Check system packages
    "${SCRIPTS_DIR}/check_system_packages.sh" >> "${INSTALL_LOG}" 2>&1

    # Find Python
    PYTHON_INFO=$("${SCRIPTS_DIR}/find_python.sh" 2>&1)
    if [ $? -ne 0 ]; then
        echo "ERROR: $PYTHON_INFO" | tee -a "${INSTALL_LOG}"
        exit 1
    fi

    PYTHON3=$(echo "$PYTHON_INFO" | cut -d'|' -f1)
    PYTHON_PATH=$(echo "$PYTHON_INFO" | cut -d'|' -f2)

    echo "✓ Found Python: $PYTHON3" | tee -a "${INSTALL_LOG}"
    $PYTHON3 --version >> "${INSTALL_LOG}" 2>&1

    # Ensure pip is available - try multiple methods
    PIP3=""
    
    # Method 1: Try python3 -m pip (preferred)
    if $PYTHON3 -m pip --version >> "${INSTALL_LOG}" 2>&1; then
        PIP3="$PYTHON3 -m pip"
    # Method 2: Try pip3 command directly
    elif command -v pip3 >/dev/null 2>&1; then
        PIP3="pip3"
        pip3 --version >> "${INSTALL_LOG}" 2>&1 || true
    # Method 3: Try to bootstrap pip with ensurepip
    else
        if $PYTHON3 -m ensurepip --upgrade >> "${INSTALL_LOG}" 2>&1; then
            if $PYTHON3 -m pip --version >> "${INSTALL_LOG}" 2>&1; then
                PIP3="$PYTHON3 -m pip"
            fi
        fi
    fi
    
    # Method 4: Try to install python3-pip automatically (if we have root/sudo access)
    if [ -z "$PIP3" ] && command -v apt-get >/dev/null 2>&1; then
        INSTALL_CMD=""
        if [ "$(id -u)" -eq 0 ]; then
            INSTALL_CMD="apt-get"
        elif command -v sudo >/dev/null 2>&1 && sudo -n true 2>/dev/null; then
            INSTALL_CMD="sudo apt-get"
        fi
        
    if [ -n "$INSTALL_CMD" ]; then
        if $INSTALL_CMD update -qq >> "${INSTALL_LOG}" 2>&1 && \
           $INSTALL_CMD install -y python3-pip >> "${INSTALL_LOG}" 2>&1; then
            sleep 0.5
            if $PYTHON3 -m pip --version >> "${INSTALL_LOG}" 2>&1; then
                PIP3="$PYTHON3 -m pip"
            elif command -v pip3 >/dev/null 2>&1; then
                PIP3="pip3"
            fi
        fi
    fi
    fi
    
    # If still no pip, give helpful error
    if [ -z "$PIP3" ]; then
        echo "ERROR: pip not available. Install with: sudo apt-get install python3-pip" | tee -a "${INSTALL_LOG}"
        exit 1
    fi
    
    echo "✓ Using pip: $PIP3" >> "${INSTALL_LOG}"

    # Update log with Python and pip info
    echo "Python: $PYTHON3 ($PYTHON_PATH)" >> "${INSTALL_LOG}"
    echo "Pip: $PIP3" >> "${INSTALL_LOG}"

    # Install Python dependencies
    echo "Installing dependencies..." | tee -a "${INSTALL_LOG}"
    # Use tee to show output while also logging it
    # install_python_deps.sh already suppresses verbose venv removal output
    "${SCRIPTS_DIR}/install_python_deps.sh" "${PLUGIN_DIR}" "${PYTHON3}" 2>&1 | tee -a "${INSTALL_LOG}"
    if [ $? -ne 0 ]; then
        echo "ERROR: Failed to install Python dependencies. Check ${INSTALL_LOG}" | tee -a "${INSTALL_LOG}"
        exit 1
    fi
    echo "✓ Dependencies installed" | tee -a "${INSTALL_LOG}"
else
    # For updates, still record the Python executable (if available) for logging
    PYTHON_INFO=$("${SCRIPTS_DIR}/find_python.sh" 2>&1)
    PYTHON3=$(echo "$PYTHON_INFO" | cut -d'|' -f1)
    PYTHON_PATH=$(echo "$PYTHON_INFO" | cut -d'|' -f2)
    echo "Python (cached): $PYTHON3 ($PYTHON_PATH)" >> "${INSTALL_LOG}"

    if [ "${UPDATED_USING_SCRIPT}" -eq 1 ]; then
        echo "Ensuring Python dependencies after update..." | tee -a "${INSTALL_LOG}"
        if [ -n "$PYTHON3" ]; then
            # Install dependencies first (critical for service startup)
            echo "Installing dependencies..." | tee -a "${INSTALL_LOG}"
            "${SCRIPTS_DIR}/install_python_deps.sh" "${PLUGIN_DIR}" "$PYTHON3" 2>&1 | tee -a "${INSTALL_LOG}" || \
                echo "Warning: Could not install Python dependencies" | tee -a "${INSTALL_LOG}"
        fi
    fi
fi

# Create data directory and make scripts executable
DATA_DIR="${SCRIPTS_DIR}"
mkdir -p "${DATA_DIR}"
chmod +x "${PLUGIN_DIR}/scripts/"*.sh "${PLUGIN_DIR}/scripts/"*.py 2>/dev/null || true

# Check and start services
if command -v systemctl >/dev/null 2>&1; then
    if ! systemctl is-active --quiet avahi-daemon 2>/dev/null; then
        echo "WARNING: avahi-daemon not running. Start with: sudo systemctl start avahi-daemon" >> "${INSTALL_LOG}"
    fi
    
    if ! systemctl is-active --quiet mosquitto 2>/dev/null; then
        if systemctl start mosquitto 2>/dev/null || sudo systemctl start mosquitto 2>/dev/null; then
            systemctl enable mosquitto 2>/dev/null || sudo systemctl enable mosquitto 2>/dev/null || true
        else
            echo "WARNING: Could not start mosquitto. MQTT control will not work." >> "${INSTALL_LOG}"
        fi
    fi
fi

# Start HomeKit service after install/update
if [ $FIRST_INSTALL -eq 1 ] || [ "${UPDATED_USING_SCRIPT}" -eq 1 ]; then
    if [ -x "${PLUGIN_DIR}/scripts/postStart.sh" ]; then
        echo "Starting HomeKit service..." | tee -a "${INSTALL_LOG}"
        # Start service after dependencies are confirmed installed
        if command -v sudo >/dev/null 2>&1; then
            sudo -u fpp MEDIADIR="${MEDIADIR}" bash "${PLUGIN_DIR}/scripts/postStart.sh" >> "${INSTALL_LOG}" 2>&1 || true
        else
            MEDIADIR="${MEDIADIR}" bash "${PLUGIN_DIR}/scripts/postStart.sh" >> "${INSTALL_LOG}" 2>&1 || true
        fi
    fi
fi

# Dependencies and service are already completed above, proceed with SHA update

# Update pluginInfo.json SHA after update (so FPP knows plugin is up to date)
if [ $FIRST_INSTALL -eq 0 ]; then
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
            echo "Using remote SHA: ${REMOTE_SHA}" >> "${INSTALL_LOG}"
        else
            echo "Using local HEAD SHA: ${CURRENT_SHA}" >> "${INSTALL_LOG}"
        fi
    fi

    # Method 2: If no git repo, try to get SHA from FPP's plugin update mechanism
    # FPP stores the remote SHA somewhere - check if we can find it
    if [ -z "${CURRENT_SHA}" ]; then
        # Check if FPP has stored the remote SHA somewhere
        # FPP might pass it as an environment variable or store it
        if [ -n "${FPP_PLUGIN_SHA}" ]; then
            CURRENT_SHA="${FPP_PLUGIN_SHA}"
            echo "Using SHA from FPP environment: ${CURRENT_SHA}" >> "${INSTALL_LOG}"
        fi
    fi

    # Method 3: Try to get SHA from the srcURL by querying GitHub API
    if [ -z "${CURRENT_SHA}" ] && [ -f "${PLUGIN_INFO_FILE}" ] && command -v curl >/dev/null 2>&1; then
        SRC_URL=$(grep -o '"srcURL":\s*"[^"]*"' "${PLUGIN_INFO_FILE}" | cut -d'"' -f4)
        if [ -n "${SRC_URL}" ] && echo "${SRC_URL}" | grep -q "github.com"; then
            REPO_PATH=$(echo "${SRC_URL}" | sed 's/.*github.com[:/]\([^.]*\)\.git/\1/' | sed 's/.*github.com\/\([^.]*\)\.git/\1/')
            BRANCH_NAME=$(grep -o '"branch":\s*"[^"]*"' "${PLUGIN_INFO_FILE}" | cut -d'"' -f4 || echo "master")
            API_URL="https://api.github.com/repos/${REPO_PATH}/commits/${BRANCH_NAME}"

            SHA_RESPONSE=$(curl -s "${API_URL}" 2>/dev/null)
            if [ $? -eq 0 ] && [ -n "${SHA_RESPONSE}" ]; then
                API_SHA=$(echo "${SHA_RESPONSE}" | grep -o '"sha":\s*"[^"]*"' | head -1 | cut -d'"' -f4)
                if [ -n "${API_SHA}" ]; then
                    CURRENT_SHA="${API_SHA}"
                    CURRENT_BRANCH="${BRANCH_NAME}"
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
            if [ $SHA_UPDATE_RESULT -ne 0 ]; then
                echo "Warning: Failed to update plugin SHA" >> "${INSTALL_LOG}"
            fi
        fi
    fi

    # Restart service if it was running
    PID_FILE="${SCRIPTS_DIR}/homekit_service.pid"
    SERVICE_WAS_RUNNING=0

    if [ -f "${PID_FILE}" ]; then
        OLD_PID=$(cat "${PID_FILE}" 2>/dev/null)
        if [ -n "$OLD_PID" ]; then
            if command -v ps >/dev/null 2>&1 && ps -p "${OLD_PID}" > /dev/null 2>&1; then
                SERVICE_WAS_RUNNING=1
            elif [ -f "/proc/${OLD_PID}" ]; then
                SERVICE_WAS_RUNNING=1
            fi
        fi
    fi

    if [ $SERVICE_WAS_RUNNING -eq 1 ]; then
        if [ -f "${SCRIPTS_DIR}/postStop.sh" ]; then
            "${SCRIPTS_DIR}/postStop.sh" >> "${INSTALL_LOG}" 2>&1
        fi
        sleep 0.5
        if [ -f "${SCRIPTS_DIR}/postStart.sh" ]; then
            "${SCRIPTS_DIR}/postStart.sh" >> "${INSTALL_LOG}" 2>&1
        fi
    fi
fi

echo "✓ Installation complete"
echo "Installation log: ${INSTALL_LOG}"
echo ""
echo "made with ❤️ in PA + MA"
