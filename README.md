# FPP HomeKit Integration Plugin

Integrates Falcon Pixel Player with Apple HomeKit, allowing you to control FPP playlists via HomeKit. Provides QR code pairing and status monitoring.

## Features

- Control FPP playlists via HomeKit (turn light ON to start playlist, OFF to stop)
- QR code pairing for easy setup
- Real-time status monitoring
- Configuration page to select which playlist to start
- Apple-style UI with dark mode support

## Installation

1. Install the plugin through FPP's plugin manager
2. Open the Status page and choose which playlist should start when HomeKit turns the accessory ON
3. Scan the QR code on the Status page with the Home app on your iOS device
4. Control the accessory from HomeKit (turn ON to start the playlist, OFF to stop)

## Requirements

- FPP 9.0 or later
- Python 3.6+
- HAP-python library (installed automatically)
- avahi-daemon (for mDNS/Bonjour support)
- iOS device with Home app (iOS 10 or later)

## Usage

1. **Configure Playlist**: On the Status page, select which playlist should start when HomeKit turns the light ON
2. **Pair with HomeKit**: On the Status page, scan the QR code with your iPhone/iPad using the Home app
3. **Control FPP**: Once paired, control FPP from the Home app - turn the light ON to start your playlist, OFF to stop

## Troubleshooting

- **Service not running**: Check that Python dependencies are installed and avahi-daemon is running
- **Can't pair**: Ensure service is running and your iOS device is on the same network
- **Playlist doesn't start**: Verify the playlist name is correctly configured and exists in FPP
