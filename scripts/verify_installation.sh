#!/bin/bash

# FPP HomeKit Plugin Installation Verification
# This script verifies that all components are properly installed and configured

PLUGIN_DIR=$(dirname $(dirname $(realpath $0)))
VENV_DIR="$PLUGIN_DIR/venv"

echo "🔍 FPP HomeKit Plugin Installation Verification"
echo "=============================================="

# Check basic file structure
echo ""
echo "📁 Checking file structure..."
files_ok=true
for file in "scripts/homekit_service.py" "scripts/install_venv.sh" "scripts/requirements.txt" "api.php" "status.php"; do
    if [ -f "$PLUGIN_DIR/$file" ]; then
        echo "✅ $file"
    else
        echo "❌ Missing: $file"
        files_ok=false
    fi
done

# Check virtual environment
echo ""
echo "🐍 Checking virtual environment..."
if [ -d "$VENV_DIR" ]; then
    echo "✅ Virtual environment exists"

    if [ -f "$VENV_DIR/bin/python3" ]; then
        echo "✅ Python executable found"

        # Test venv functionality
        echo "🧪 Testing venv functionality..."
        source "$VENV_DIR/bin/activate"
        python3 -c "
import sys
try:
    import pyhap
    import paho.mqtt
    import requests
    print('✅ All required packages imported successfully')
except ImportError as e:
    print(f'❌ Import error: {e}')
    sys.exit(1)
"
        if [ $? -eq 0 ]; then
            echo "✅ Virtual environment is functional"
        else
            echo "❌ Virtual environment test failed"
            files_ok=false
        fi
    else
        echo "❌ Python executable missing in venv"
        files_ok=false
    fi
else
    echo "❌ Virtual environment not found (run ./scripts/install_venv.sh)"
    files_ok=false
fi

# Check configuration files
echo ""
echo "⚙️ Checking configuration..."
if [ -f "$PLUGIN_DIR/scripts/homekit_config.json" ]; then
    echo "✅ Configuration file exists"
else
    echo "⚠️ Configuration file missing (will be created on first run)"
fi

# Check system dependencies
echo ""
echo "🔧 Checking system dependencies..."
deps_ok=true
commands=("python3" "pip3" "mosquitto_pub")
for cmd in "${commands[@]}"; do
    if command -v "$cmd" >/dev/null 2>&1; then
        echo "✅ $cmd available"
    else
        echo "❌ $cmd missing"
        deps_ok=false
    fi
done

# Check MQTT broker
echo ""
echo "📡 Checking MQTT broker..."
if pgrep -f mosquitto >/dev/null 2>&1; then
    echo "✅ MQTT broker is running"
else
    echo "⚠️ MQTT broker not detected (may be normal if using remote broker)"
fi

# Final summary
echo ""
echo "🎯 Installation Summary:"
if [ "$files_ok" = true ] && [ "$deps_ok" = true ]; then
    echo "✅ Installation appears complete and ready!"
    echo ""
    echo "🚀 Next steps:"
    echo "1. Configure playlist in FPP web interface"
    echo "2. Start the HomeKit service"
    echo "3. Pair with Apple Home app using QR code"
    echo ""
    echo "📖 For detailed setup instructions, see README.md"
else
    echo "❌ Installation incomplete. Please address the issues above."
    exit 1
fi

echo ""
echo "Made with ❤️ in PA + MA"
