#!/bin/bash

# Get the plugin directory
PLUGIN_DIR=$(dirname $(dirname $(realpath $0)))
VENV_DIR="$PLUGIN_DIR/venv"
REQUIREMENTS_FILE="$PLUGIN_DIR/scripts/requirements.txt"

# Create requirements.txt if it doesn't exist
if [ ! -f "$REQUIREMENTS_FILE" ]; then
    cat > "$REQUIREMENTS_FILE" << EOF
HAP-python[QRCode]>=4.5.0
paho-mqtt>=1.6.1
requests>=2.28.0
EOF
fi

# Create venv if it doesn't exist
if [ ! -d "$VENV_DIR" ] || [ ! -x "$VENV_DIR/bin/python3" ]; then
    rm -rf "$VENV_DIR" 2>/dev/null
    python3 -m venv "$VENV_DIR" > /dev/null 2>&1
    if [ $? -ne 0 ]; then
        echo "ERROR: Failed to create virtual environment."
        exit 1
    fi
fi

VENV_PYTHON="$VENV_DIR/bin/python3"
VENV_PIP="$VENV_DIR/bin/pip"

if [ ! -x "$VENV_PYTHON" ]; then
    echo "ERROR: Virtual environment python executable not found at $VENV_PYTHON"
    exit 1
fi

if [ ! -x "$VENV_PIP" ]; then
    echo "ERROR: Virtual environment pip executable not found at $VENV_PIP"
    exit 1
fi

# Upgrade pip first
"$VENV_PIP" install --upgrade pip > /dev/null 2>&1

# Install requirements from file
if [ -f "$REQUIREMENTS_FILE" ]; then
    if ! "$VENV_PIP" install --upgrade -r "$REQUIREMENTS_FILE" > /dev/null 2>&1; then
        echo "ERROR: Failed to install dependencies from requirements.txt."
        exit 1
    fi
fi

# Ensure critical dependencies are present
if ! "$VENV_PIP" install --upgrade "HAP-python[QRCode]>=4.5.0" "paho-mqtt>=1.6.1" "requests>=2.28.0" > /dev/null 2>&1; then
    echo "ERROR: Failed to install core HomeKit dependencies."
    exit 1
fi

# Test the venv setup
if [ -x "$VENV_PYTHON" ]; then
    "$VENV_PYTHON" "$PLUGIN_DIR/scripts/test_venv.py" > /dev/null 2>&1
    if [ $? -ne 0 ]; then
        echo "ERROR: Venv test failed."
        exit 1
    fi
fi

