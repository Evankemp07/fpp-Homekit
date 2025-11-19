# FPP HomeKit Plugin

A HomeKit integration plugin for Falcon Player (FPP) that enables seamless control of FPP playlists through Apple HomeKit. Built with [HAP-python](https://github.com/ikalchev/HAP-python), this plugin exposes FPP as a controllable light accessory in Apple's Home app.

## Overview

Transform your FPP installation into a HomeKit-compatible smart light. Turn the light **ON** to start your configured playlist, turn it **OFF** to stop playback. Perfect for integrating your holiday light displays into your smart home ecosystem.

## Features

- **Native HomeKit Integration**: Built on HAP-python for reliable, standards-compliant HomeKit support
- **Instant Response**: HomeKit commands process in under 100ms with background thread handling
- **Real-time Synchronization**: MQTT-based bidirectional status updates keep HomeKit and FPP in sync
- **Auto-turn-off**: Light automatically turns off when playlists finish playing
- **QR Code Pairing**: Easy setup with scannable QR codes displayed in the web interface
- **Command History**: View the last command received (both real HomeKit and emulated commands)
- **Modern Web Interface**: Beautiful Apple-style UI with real-time status monitoring
- **Isolated Dependencies**: Python virtual environment ensures clean, conflict-free installation

## How It Works

This plugin uses [HAP-python](https://github.com/ikalchev/HAP-python) to implement the HomeKit Accessory Protocol (HAP). The service runs as a background Python process that:

1. **Exposes FPP as a Light Accessory**: Your FPP instance appears as a controllable light in the Home app
2. **Handles HomeKit Commands**: When you turn the light ON/OFF in Home app, commands are processed instantly
3. **Communicates via MQTT**: Commands and status updates flow between HomeKit and FPP through MQTT
4. **Auto-discovers on Network**: Uses mDNS/Bonjour for automatic discovery by iOS devices

## Installation

### Quick Start

1. **Install via FPP Plugin Manager**: Navigate to Content Setup → Plugins in FPP web interface
2. **Configure Playlist**: Select which playlist should start when HomeKit turns the light ON
3. **Pair with HomeKit**: Scan the QR code in the plugin interface with your iPhone/iPad Home app
4. **Start Controlling**: Turn the light ON/OFF in Home app to control your FPP playlist

### Manual Installation

If installing manually:

```bash
cd /home/fpp/media/plugins/fpp-Homekit/scripts
bash fpp_install.sh
```

The installation script automatically:
- Creates a Python virtual environment
- Installs HAP-python and all required dependencies
- Sets up the HomeKit service

## Requirements

- **FPP**: Version 9.0 or later
- **Python**: 3.6 or later (Python 3.11+ recommended)
- **HAP-python**: Automatically installed via pip (version 4.5.0+)
- **MQTT**: FPP must have MQTT configured and running
- **mDNS/Bonjour**: avahi-daemon for network discovery
- **iOS Device**: iPhone/iPad with Home app (iOS 10+)

## Usage

### Web Interface

The plugin provides a comprehensive web interface accessible through FPP's plugin menu:

- **Status Monitoring**: Real-time display of HomeKit service status, FPP connection, and pairing state
- **Playlist Configuration**: Select which playlist starts when HomeKit turns the light ON
- **QR Code Display**: Scan with Home app for easy pairing
- **Command History**: View the last command received through HomeKit
- **MQTT Settings**: Advanced configuration for MQTT broker connection
- **Network Configuration**: Choose which network interface HomeKit should listen on

### HomeKit Control

Once paired, control FPP directly from Apple's Home app:

- **Turn Light ON**: Starts your configured playlist
- **Turn Light OFF**: Stops current playback
- **Status Sync**: Light state automatically reflects FPP's actual playing status
- **Auto-turn-off**: Light turns off automatically when playlist finishes

## Architecture

```
iOS Home App
    ↕
HomeKit Accessory Protocol (HAP)
    ↕
HAP-python Library
    ↕
Python Service (homekit_service.py)
    ↕
MQTT
    ↕
FPP
```

**Key Components:**
- **HAP-python**: Provides HomeKit Accessory Protocol implementation
- **homekit_service.py**: Main service that bridges HomeKit and FPP
- **MQTT**: Communication layer for commands and status updates
- **Virtual Environment**: Isolated Python dependencies in `venv/`

## Technical Details

### Dependencies

The plugin uses the following Python libraries (installed automatically):

- **[HAP-python](https://github.com/ikalchev/HAP-python)**: HomeKit Accessory Protocol implementation
- **paho-mqtt**: MQTT client for FPP communication
- **requests**: HTTP client for FPP API calls
- **qrcode[pil]**: QR code generation for pairing

### File Structure

- `scripts/homekit_service.py`: Main HomeKit accessory service (uses HAP-python)
- `scripts/requirements.txt`: Python dependencies including HAP-python
- `scripts/install_venv.sh`: Virtual environment setup
- `api.php`: Web interface API endpoints
- `status.php`: Web interface UI
- `styles.css`: Modern Apple-style styling

## Troubleshooting

### Service Won't Start

```bash
# Check service logs
tail -f /home/fpp/media/plugins/fpp-Homekit/scripts/homekit_service.log

# Verify Python dependencies
cd /home/fpp/media/plugins/fpp-Homekit/scripts
./verify_installation.sh
```

### Can't Pair with HomeKit

- Ensure FPP and iOS device are on the same network
- Check that port 51826 is accessible (HomeKit default port)
- Verify the QR code and setup code match
- Ensure avahi-daemon is running: `sudo systemctl status avahi-daemon`

### MQTT Connection Issues

- Verify MQTT broker is running: `sudo systemctl status mosquitto`
- Check MQTT settings in FPP web interface
- Test MQTT connection using the "Test MQTT" button in plugin settings

### Playlist Doesn't Start

- Verify playlist name is correctly configured in plugin settings
- Ensure playlist exists in FPP
- Check MQTT connection status in plugin interface

## Development

### Updating the Plugin

```bash
cd /home/fpp/media/plugins/fpp-Homekit
bash scripts/update_plugin.sh
bash scripts/fpp_install.sh
```

### Adding Dependencies

1. Add package to `scripts/requirements.txt`
2. Run `bash scripts/fpp_install.sh` to update the virtual environment

### Modifying the Service

1. Edit `scripts/homekit_service.py`
2. Restart the service via web interface or:
   ```bash
   cd /home/fpp/media/plugins/fpp-Homekit/scripts
   MEDIADIR=/home/fpp/media bash postStop.sh
   MEDIADIR=/home/fpp/media bash postStart.sh
   ```

## Credits

Built with [HAP-python](https://github.com/ikalchev/HAP-python) by [ikalchev](https://github.com/ikalchev) - a Python implementation of the HomeKit Accessory Protocol.

## License

This plugin is provided as-is for use with Falcon Player.
