#!/usr/bin/env python3
"""
FPP HomeKit Integration Service
Exposes FPP as a HomeKit Light accessory using HAP-python
"""

import os
import sys
import json
import time
import logging
import threading
import requests
from pyhap.accessory import Accessory, Bridge
from pyhap.accessory_driver import AccessoryDriver
from pyhap.const import CATEGORY_LIGHTBULB
try:
    from pyhap import qr
    QR_AVAILABLE = True
except ImportError:
    QR_AVAILABLE = False

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s'
)
logger = logging.getLogger(__name__)

# Get plugin directory
PLUGIN_DIR = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
CONFIG_FILE = os.path.join(PLUGIN_DIR, 'scripts', 'homekit_config.json')
PID_FILE = os.path.join(PLUGIN_DIR, 'scripts', 'homekit_service.pid')

# Default FPP API settings
FPP_HOST = 'localhost'
FPP_PORT = 32320
FPP_API_BASE = f'http://{FPP_HOST}:{FPP_PORT}/api'

class FPPLightAccessory(Accessory):
    """HomeKit Light accessory that controls FPP playlists"""
    
    category = CATEGORY_LIGHTBULB
    
    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        
        # Load configuration
        self.config = self.load_config()
        self.playlist_name = self.config.get('playlist_name', '')
        
        # Add Light service with On characteristic
        service = self.add_preload_service('Lightbulb')
        self.on_char = service.configure_char('On', value=False, setter_callback=self.set_on)
        
        # State tracking
        self.is_on = False
        self.fpp_status_polling = True
        
        # Start status polling thread
        self.poll_thread = threading.Thread(target=self.poll_fpp_status, daemon=True)
        self.poll_thread.start()
        
        logger.info(f"FPP Light Accessory initialized with playlist: {self.playlist_name}")
    
    def load_config(self):
        """Load configuration from JSON file"""
        default_config = {
            'playlist_name': ''
        }
        
        if os.path.exists(CONFIG_FILE):
            try:
                with open(CONFIG_FILE, 'r') as f:
                    config = json.load(f)
                    default_config.update(config)
            except Exception as e:
                logger.error(f"Error loading config: {e}")
        
        return default_config
    
    def save_config(self):
        """Save configuration to JSON file"""
        try:
            with open(CONFIG_FILE, 'w') as f:
                json.dump(self.config, f, indent=2)
        except Exception as e:
            logger.error(f"Error saving config: {e}")
    
    def set_on(self, value):
        """Handle HomeKit On/Off characteristic changes"""
        logger.info(f"HomeKit set_on called with value: {value}")
        
        # Reload config to get latest playlist name
        self.config = self.load_config()
        self.playlist_name = self.config.get('playlist_name', '')
        
        if value:
            self.start_playlist()
        else:
            self.stop_playlist()
        
        self.is_on = value
        self.on_char.set_value(value)
    
    def start_playlist(self):
        """Start the configured FPP playlist"""
        if not self.playlist_name:
            logger.warning("No playlist configured")
            return
        
        try:
            url = f"{FPP_API_BASE}/playlists/{self.playlist_name}/start"
            response = requests.post(url, timeout=5)
            if response.status_code == 200:
                logger.info(f"Started playlist: {self.playlist_name}")
            else:
                logger.error(f"Failed to start playlist: {response.status_code} - {response.text}")
        except Exception as e:
            logger.error(f"Error starting playlist: {e}")
    
    def stop_playlist(self):
        """Stop FPP playback"""
        try:
            url = f"{FPP_API_BASE}/command/Stop"
            response = requests.post(url, timeout=5)
            if response.status_code == 200:
                logger.info("Stopped FPP playback")
            else:
                logger.error(f"Failed to stop playback: {response.status_code} - {response.text}")
        except Exception as e:
            logger.error(f"Error stopping playback: {e}")
    
    def get_fpp_status(self):
        """Get current FPP playback status"""
        try:
            url = f"{FPP_API_BASE}/status"
            response = requests.get(url, timeout=5)
            if response.status_code == 200:
                data = response.json()
                # Check if FPP is playing
                # FPP status typically has a 'status_name' field that can be 'idle', 'playing', etc.
                status_name = data.get('status_name', 'idle')
                return status_name == 'playing'
            else:
                logger.warning(f"Failed to get FPP status: {response.status_code}")
                return False
        except Exception as e:
            logger.error(f"Error getting FPP status: {e}")
            return False
    
    def poll_fpp_status(self):
        """Poll FPP status periodically to sync HomeKit state"""
        while self.fpp_status_polling:
            try:
                fpp_playing = self.get_fpp_status()
                
                # Update HomeKit state if it differs from FPP state
                if fpp_playing != self.is_on:
                    logger.info(f"Syncing HomeKit state: {fpp_playing}")
                    self.is_on = fpp_playing
                    self.on_char.set_value(fpp_playing)
                
                time.sleep(3)  # Poll every 3 seconds
            except Exception as e:
                logger.error(f"Error in status polling: {e}")
                time.sleep(5)
    
    def stop(self):
        """Stop the accessory"""
        self.fpp_status_polling = False
        super().stop()

def get_accessory(driver):
    """Create and return the FPP Light accessory"""
    return FPPLightAccessory(driver, 'FPP Light')

def main():
    """Main entry point"""
    # Write PID file
    try:
        with open(PID_FILE, 'w') as f:
            f.write(str(os.getpid()))
    except Exception as e:
        logger.error(f"Error writing PID file: {e}")
    
    # Create driver
    state_file = os.path.join(PLUGIN_DIR, 'scripts', 'homekit_accessory.state')
    driver = AccessoryDriver(port=51826, persist_file=state_file)
    
    # Add accessory
    accessory = get_accessory(driver)
    driver.add_accessory(accessory=accessory)
    
    # Save setup code and setup ID for QR code generation
    try:
        setup_code = driver.state.pincode.decode()
        setup_id = driver.state.setup_id.decode() if hasattr(driver.state, 'setup_id') and driver.state.setup_id else 'HOME'
        mac = driver.state.mac.decode() if hasattr(driver.state, 'mac') and driver.state.mac else ''
        
        # Generate QR code if available
        qr_code_data = None
        if QR_AVAILABLE:
            try:
                qr_code_data = qr.get_qr_code(setup_code, setup_id)
            except Exception as e:
                logger.warning(f"Could not generate QR code: {e}")
        
        # Save to a JSON file for PHP to read
        info_file = os.path.join(PLUGIN_DIR, 'scripts', 'homekit_pairing_info.json')
        info_data = {
            'setup_code': setup_code,
            'setup_id': setup_id,
            'mac': mac
        }
        if qr_code_data:
            info_data['qr_data'] = qr_code_data
        
        with open(info_file, 'w') as f:
            json.dump(info_data, f, indent=2)
        
        logger.info(f"Setup code: {setup_code}, Setup ID: {setup_id}")
    except Exception as e:
        logger.warning(f"Could not save setup info: {e}")
    
    # Start the driver
    logger.info("Starting HomeKit service...")
    signal_handler = driver.signal_handler()
    driver.start()
    
    try:
        signal_handler.wait()
    except KeyboardInterrupt:
        logger.info("Stopping HomeKit service...")
    finally:
        driver.stop()
        # Remove PID file
        try:
            if os.path.exists(PID_FILE):
                os.remove(PID_FILE)
        except Exception as e:
            logger.error(f"Error removing PID file: {e}")

if __name__ == '__main__':
    main()

