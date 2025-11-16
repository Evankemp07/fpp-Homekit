#!/bin/bash

# Check for required system packages
# This script is called from fpp_install.sh

if [ ! -f /etc/debian_version ]; then
    # Not a Debian-based system, skip package checking
    exit 0
fi

echo "Checking for required system packages..."

# List of required packages
REQUIRED_PACKAGES="python3 python3-pip python3-dev avahi-daemon libavahi-compat-libdnssd-dev libffi-dev libssl-dev build-essential libjpeg-dev zlib1g-dev libfreetype6-dev liblcms2-dev"

MISSING_PACKAGES=""
for pkg in $REQUIRED_PACKAGES; do
    if ! dpkg -l | grep -q "^ii  $pkg "; then
        MISSING_PACKAGES="$MISSING_PACKAGES $pkg"
    fi
done

if [ -n "$MISSING_PACKAGES" ]; then
    echo "Warning: Some required system packages may be missing: $MISSING_PACKAGES"
    echo "These should be installed via pluginInfo.json dependencies, but if installation fails,"
    echo "you may need to run: sudo apt-get update && sudo apt-get install $MISSING_PACKAGES"
else
    echo "✓ All required system packages are installed"
fi

exit 0

