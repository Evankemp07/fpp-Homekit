#!/bin/bash

# Get the plugin directory
PLUGIN_DIR=$(dirname $(dirname $(realpath $0)))
VENV_DIR="$PLUGIN_DIR/venv"
REQUIREMENTS_FILE="$PLUGIN_DIR/scripts/requirements.txt"

echo "Setting up FPP-HomeKit plugin environment..."

# Create requirements.txt if it doesn't exist
if [ ! -f "$REQUIREMENTS_FILE" ]; then
    echo "Creating requirements.txt..."
    cat > "$REQUIREMENTS_FILE" << EOF
HAP-python[QRCode]>=4.5.0
paho-mqtt>=1.6.1
requests>=2.28.0
EOF
fi

# Create venv if it doesn't exist
if [ ! -d "$VENV_DIR" ]; then
    echo "Creating virtual environment in $VENV_DIR..."
    python3 -m venv "$VENV_DIR"
    if [ $? -ne 0 ]; then
        echo "ERROR: Failed to create virtual environment."
        exit 1
    fi
else
    echo "Virtual environment already exists."
fi

# Activate venv and install dependencies
echo "Installing/Updating dependencies in venv..."
source "$VENV_DIR/bin/activate"

# Upgrade pip first
pip install --upgrade pip > /dev/null 2>&1

# Install requirements
pip install -r "$REQUIREMENTS_FILE"
if [ $? -ne 0 ]; then
    echo "ERROR: Failed to install dependencies."
    exit 1
fi

echo "Environment setup complete."
echo "Python executable: $VENV_DIR/bin/python3"

# Test the venv setup
echo ""
echo "Testing venv setup..."
source "$VENV_DIR/bin/activate"
python3 "$PLUGIN_DIR/scripts/test_venv.py"

if [ $? -eq 0 ]; then
    echo ""
    echo "🎉 Venv setup complete and tested successfully!"
    echo "The HomeKit service will now use the isolated environment."
else
    echo ""
    echo "❌ Venv test failed. Check the output above."
    exit 1
fi

