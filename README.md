# FPP HomeKit Integration Plugin

Integrates Falcon Pixel Player with Apple HomeKit, allowing you to control FPP playlists via HomeKit. Provides QR code pairing and status monitoring.

## Features

- Control FPP playlists via HomeKit (turn light ON to start playlist, OFF to stop)
- QR code pairing for easy setup
- Real-time status monitoring
- Configuration page to select which playlist to start
- Apple-style UI with dark mode support

## Development

### Running the Development Server

To test the plugin locally without FPP:

```bash
./dev-start.sh
```

Or manually:
```bash
php -S localhost:8000 dev-server.php
```

Then open http://localhost:8000 in your browser.

See [DEV.md](DEV.md) for more development details.

## Installation

1. Install the plugin through FPP's plugin manager
2. Configure which playlist to start in the Configuration page
3. Go to the Status page to view the QR code
4. Pair with HomeKit using the Home app on your iOS device

## Requirements

- FPP 9.0 or later
- Python 3.6+
- HAP-python library (installed automatically)
- avahi-daemon (for mDNS/Bonjour support)
- iOS device with Home app (iOS 10 or later)

## Usage

1. **Configure Playlist**: Go to Configuration page and select which playlist should start when HomeKit turns the light ON
2. **Pair with HomeKit**: On the Status page, scan the QR code with your iPhone/iPad using the Home app
3. **Control FPP**: Once paired, control FPP from the Home app - turn the light ON to start your playlist, OFF to stop

## Troubleshooting

- **Service not running**: Check that Python dependencies are installed and avahi-daemon is running
- **Can't pair**: Ensure service is running and your iOS device is on the same network
- **Playlist doesn't start**: Verify playlist name is correctly configured and exists in FPP

See the Help page in the plugin for more troubleshooting tips.
