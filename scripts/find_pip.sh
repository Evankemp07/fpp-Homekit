#!/bin/bash

# Find and verify pip3 installation
# This script is called from fpp_install.sh
# Arguments: PYTHON3 (python3 command)
# Outputs: PIP3 (pip3 command or python3 -m pip)

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

if [ -z "$PIP3" ]; then
    echo "ERROR: pip3 not found. Please install pip3."
    echo "On Debian/Ubuntu: sudo apt-get install python3-pip"
    echo "On macOS: python3 -m ensurepip --upgrade"
    exit 1
fi

# Verify pip works
PIP_CHECK_OUTPUT=$($PIP3 --version 2>&1)
if [ $? -ne 0 ]; then
    echo "ERROR: pip3 found but not working: $PIP_CHECK_OUTPUT"
    exit 1
fi

echo "$PIP3"
exit 0

