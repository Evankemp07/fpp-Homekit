#!/bin/bash

# Find and verify pip3 installation
# This script is called from fpp_install.sh
# Arguments: PYTHON3 (python3 command)
# Outputs: PIP3 (pip3 command or python3 -m pip)
# Automatically attempts to install pip if not found

PYTHON3="${1}"

if [ -z "$PYTHON3" ]; then
    echo "ERROR: Python3 command not provided"
    exit 1
fi

# Find pip3 (prefer pip3 associated with python3)
PIP3=""
if $PYTHON3 -m pip --version >/dev/null 2>&1; then
    PIP3="$PYTHON3 -m pip"
elif command -v pip3 >/dev/null 2>&1; then
    PIP3="pip3"
elif command -v pip >/dev/null 2>&1; then
    PIP3="pip"
fi

# If pip not found, try to install it
if [ -z "$PIP3" ]; then
    echo "pip3 not found. Attempting to install pip..." >&2
    
    # Try ensurepip first (built-in Python module)
    echo "Trying ensurepip..." >&2
    ENSUREPIP_OUTPUT=$($PYTHON3 -m ensurepip --upgrade 2>&1)
    ENSUREPIP_RESULT=$?
    
    if [ $ENSUREPIP_RESULT -eq 0 ]; then
        echo "✓ Successfully installed pip using ensurepip" >&2
        # Try again after installing
        if $PYTHON3 -m pip --version >/dev/null 2>&1; then
            PIP3="$PYTHON3 -m pip"
        fi
    else
        echo "ensurepip failed (exit code: $ENSUREPIP_RESULT)" >&2
        echo "Output: $ENSUREPIP_OUTPUT" >&2
        
        # Try downloading get-pip.py as fallback
        echo "" >&2
        echo "Trying alternative: downloading get-pip.py..." >&2
        GET_PIP_URL="https://bootstrap.pypa.io/get-pip.py"
        GET_PIP_SCRIPT="/tmp/get-pip.py"
        
        DOWNLOAD_SUCCESS=0
        if command -v curl >/dev/null 2>&1; then
            if curl -sSL "$GET_PIP_URL" -o "$GET_PIP_SCRIPT" 2>&1; then
                DOWNLOAD_SUCCESS=1
            fi
        elif command -v wget >/dev/null 2>&1; then
            if wget -q -O "$GET_PIP_SCRIPT" "$GET_PIP_URL" 2>&1; then
                DOWNLOAD_SUCCESS=1
            fi
        fi
        
        if [ $DOWNLOAD_SUCCESS -eq 1 ] && [ -f "$GET_PIP_SCRIPT" ]; then
            echo "Running get-pip.py with --user flag..." >&2
            GETPIP_OUTPUT=$($PYTHON3 "$GET_PIP_SCRIPT" --user 2>&1)
            GETPIP_RESULT=$?
            
            if [ $GETPIP_RESULT -eq 0 ]; then
                echo "✓ Successfully installed pip using get-pip.py" >&2
                # Try again after installing
                if $PYTHON3 -m pip --version >/dev/null 2>&1; then
                    PIP3="$PYTHON3 -m pip"
                fi
            else
                echo "get-pip.py with --user failed (exit code: $GETPIP_RESULT)" >&2
                echo "Output: $GETPIP_OUTPUT" >&2
                
                # On some systems, try with --break-system-packages flag
                if [ -f /etc/debian_version ]; then
                    echo "" >&2
                    echo "Trying with --break-system-packages flag..." >&2
                    GETPIP_OUTPUT=$($PYTHON3 "$GET_PIP_SCRIPT" --user --break-system-packages 2>&1)
                    GETPIP_RESULT=$?
                    
                    if [ $GETPIP_RESULT -eq 0 ]; then
                        echo "✓ Successfully installed pip using get-pip.py" >&2
                        if $PYTHON3 -m pip --version >/dev/null 2>&1; then
                            PIP3="$PYTHON3 -m pip"
                        fi
                    fi
                fi
            fi
            rm -f "$GET_PIP_SCRIPT"
        else
            echo "Could not download get-pip.py (curl/wget not available or download failed)" >&2
        fi
    fi
    
    # Final check - if still no pip, provide helpful error message
    if [ -z "$PIP3" ]; then
        echo "" >&2
        echo "ERROR: pip is still not available after attempting to install." >&2
        echo "" >&2
        if [ -f /etc/debian_version ]; then
            echo "This system uses Debian/Ubuntu's externally-managed Python environment (PEP 668)," >&2
            echo "which prevents installing pip without the system package." >&2
            echo "" >&2
            echo "The python3-pip package MUST be installed via the system package manager:" >&2
            echo "  sudo apt-get update" >&2
            echo "  sudo apt-get install python3-pip" >&2
        else
            echo "Please install pip manually:" >&2
            echo "  $PYTHON3 -m ensurepip --upgrade" >&2
            echo "Or on macOS:" >&2
            echo "  python3 -m ensurepip --upgrade" >&2
        fi
        exit 1
    fi
fi

# Verify pip works
PIP_CHECK_OUTPUT=$($PIP3 --version 2>&1)
if [ $? -ne 0 ]; then
    echo "ERROR: pip3 found but not working: $PIP_CHECK_OUTPUT"
    exit 1
fi

echo "$PIP3"
exit 0

