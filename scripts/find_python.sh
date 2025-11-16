#!/bin/bash

# Find and verify Python 3 installation
# This script is called from fpp_install.sh
# Outputs: PYTHON3_PATH (full path to python3 executable)

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

# Get full path to Python executable
PYTHON_PATH=$($PYTHON3 -c "import sys; print(sys.executable)" 2>/dev/null)
if [ -z "$PYTHON_PATH" ]; then
    PYTHON_PATH=$(which $PYTHON3)
fi

echo "$PYTHON3|$PYTHON_PATH"
exit 0

