#!/usr/bin/env python3
"""
FPP HomeKit Integration Service
Exposes FPP as a HomeKit Light accessory using HAP-python
"""

import os
import sys
import site

# Ensure user-installed packages are available
# This is important when packages are installed with --user flag
site.ENABLE_USER_SITE = True

# Add user site-packages to path using the proper method
try:
    user_site = site.getusersitepackages()
    if user_site and os.path.exists(user_site):
        site.addsitedir(user_site)
except Exception:
    pass

# Also ensure system site-packages are available
try:
    for site_dir in site.getsitepackages():
        if os.path.exists(site_dir):
            site.addsitedir(site_dir)
except Exception:
    pass

# Update sys.path directly if necessary (avoid reloading site which can undo addsitedir changes)
_extra_paths = []
try:
    if user_site and user_site not in sys.path:
        _extra_paths.append(user_site)
except NameError:
    pass
try:
    for site_dir in site.getsitepackages():
        if site_dir not in sys.path:
            _extra_paths.append(site_dir)
except Exception:
    pass
if _extra_paths:
    sys.path[:0] = _extra_paths

import json
import time
import logging
import threading
import signal
from typing import List

# Try importing required modules with helpful error messages
try:
    import requests
except ImportError as e:
    print(f"ERROR: Failed to import requests: {e}", file=sys.stderr)
    print(f"Python executable: {sys.executable}", file=sys.stderr)
    print(f"Python path: {sys.path}", file=sys.stderr)
    print(f"User site-packages: {site.getusersitepackages()}", file=sys.stderr)
    sys.exit(1)

try:
    import paho.mqtt.client as mqtt
    MQTT_AVAILABLE = True
except ImportError as e:
    print(f"ERROR: Failed to import paho.mqtt: {e}", file=sys.stderr)
    print(f"Install with: {sys.executable} -m pip install paho-mqtt --user", file=sys.stderr)
    MQTT_AVAILABLE = False

try:
    from pyhap.accessory import Accessory
    from pyhap.accessory_driver import AccessoryDriver
    from pyhap.const import CATEGORY_LIGHTBULB
except ImportError as e:
    print(f"ERROR: Failed to import pyhap (HAP-python): {e}", file=sys.stderr)
    print(f"Python executable: {sys.executable}", file=sys.stderr)
    print(f"Python version: {sys.version}", file=sys.stderr)
    print(f"Python path ({len(sys.path)} entries):", file=sys.stderr)
    for i, path in enumerate(sys.path[:10]):  # Show first 10 paths
        print(f"  [{i}] {path}", file=sys.stderr)
    if len(sys.path) > 10:
        print(f"  ... and {len(sys.path) - 10} more", file=sys.stderr)
    
    try:
        user_site = site.getusersitepackages()
        print(f"User site-packages: {user_site}", file=sys.stderr)
        if user_site and os.path.exists(user_site):
            print(f"  (exists: {os.path.exists(user_site)})", file=sys.stderr)
            # Check if pyhap is actually there
            pyhap_path = os.path.join(user_site, 'pyhap')
            if os.path.exists(pyhap_path):
                print(f"  WARNING: pyhap directory found at {pyhap_path} but Python can't import it!", file=sys.stderr)
        else:
            print(f"  (does not exist)", file=sys.stderr)
    except Exception as site_err:
        print(f"Could not determine user site-packages: {site_err}", file=sys.stderr)
    
    print("", file=sys.stderr)
    print("To fix this, install HAP-python from https://github.com/ikalchev/HAP-python:", file=sys.stderr)
    print(f"  {sys.executable} -m pip install 'HAP-python[QRCode]>=4.0.0' --user", file=sys.stderr)
    print("Or system-wide:", file=sys.stderr)
    print(f"  sudo {sys.executable} -m pip install 'HAP-python[QRCode]>=4.0.0'", file=sys.stderr)
    print("", file=sys.stderr)
    print("Also ensure all dependencies are installed:", file=sys.stderr)
    req_file = os.path.join(os.path.dirname(__file__), 'requirements.txt')
    print(f"  {sys.executable} -m pip install -r {req_file} --user", file=sys.stderr)
    print("", file=sys.stderr)
    print("After installing, verify with:", file=sys.stderr)
    print(f"  {sys.executable} -c 'import site; site.ENABLE_USER_SITE = True; import pyhap; print(\"Success!\")'", file=sys.stderr)
    sys.exit(1)

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

# ---------------------------------------------------------------------------
# FPP API discovery helpers
# ---------------------------------------------------------------------------

def _read_http_port_from_settings(settings_path: str) -> int:
    """Parse the FPP settings file to extract HTTPPort if present."""
    if not settings_path or not os.path.isfile(settings_path):
        return 0
    try:
        with open(settings_path, 'r', encoding='utf-8', errors='ignore') as fh:
            for line in fh:
                if line.strip().startswith('HTTPPort'):
                    parts = line.split('=', 1)
                    if len(parts) == 2:
                        port_str = parts[1].strip()
                        if port_str.isdigit():
                            port = int(port_str)
                            if port > 0:
                                return port
    except Exception as err:
        logger.debug("Unable to parse FPP settings at %s: %s", settings_path, err)
    return 0


def _candidate_settings_paths() -> List[str]:
    """Return probable locations of the FPP settings file."""
    candidates = []
    fppdir = os.environ.get('FPPDIR')
    if fppdir:
        candidates.append(os.path.join(fppdir, 'settings'))
    mediadir = os.environ.get('MEDIADIR')
    if mediadir:
        candidates.append(os.path.join(mediadir, 'settings'))
        candidates.append(os.path.join(os.path.dirname(mediadir), 'settings'))
    candidates.extend([
        '/home/fpp/media/settings',
        '/opt/fpp/media/settings',
    ])
    # Deduplicate while preserving order
    seen = set()
    ordered = []
    for path in candidates:
        if path and path not in seen:
            seen.add(path)
            ordered.append(path)
    return ordered


def build_fpp_api_endpoints() -> List[str]:
    """Create a ordered list of candidate FPP API base URLs."""
    hosts = []
    ports = []
    
    # First priority: Read from detected API config file (created by install script)
    plugin_dir = os.path.dirname(os.path.abspath(__file__))
    api_config_file = os.path.join(plugin_dir, 'fpp_api_config.json')
    if os.path.exists(api_config_file):
        try:
            with open(api_config_file, 'r') as f:
                api_config = json.load(f)
                if api_config.get('host'):
                    hosts.append(api_config['host'])
                if api_config.get('port'):
                    ports.append(int(api_config['port']))
                    logger.info("Using detected FPP API: %s:%s", api_config.get('host', 'localhost'), api_config.get('port'))
        except Exception as e:
            logger.warning("Failed to read API config file %s: %s", api_config_file, e)
    
    # Second priority: Environment variables
    env_host = os.environ.get('FPP_API_HOST')
    if env_host and env_host not in hosts:
        hosts.append(env_host)
    
    env_port = os.environ.get('FPP_API_PORT')
    if env_port:
        try:
            port_val = int(env_port)
            if port_val > 0 and port_val not in ports:
                ports.append(port_val)
        except ValueError:
            logger.debug("Ignoring invalid FPP_API_PORT value: %s", env_port)
    
    # Third priority: Read from FPP settings files
    for path in _candidate_settings_paths():
        port_from_file = _read_http_port_from_settings(path)
        if port_from_file > 0 and port_from_file not in ports:
            ports.append(port_from_file)
    
    # Add default hosts if none found
    if not hosts:
        hosts.extend(['localhost', '127.0.0.1'])
    
    # Add default ports if none found
    if not ports:
        ports.extend([32320, 80, 8080])  # Default FPP port first

    # Deduplicate positive values while preserving order
    seen_ports = set()
    ordered_ports = []
    for port in ports:
        if isinstance(port, int) and port > 0 and port not in seen_ports:
            seen_ports.add(port)
            ordered_ports.append(port)

    endpoints = []
    for host in hosts:
        for port in ordered_ports:
            if port == 80:
                endpoints.append(f'http://{host}/api')
            else:
                endpoints.append(f'http://{host}:{port}/api')

    # Final dedupe preserving order
    endpoints = list(dict.fromkeys(endpoints))

    if not endpoints:
        endpoints = ['http://localhost/api']

    logger.info("Discovered FPP API endpoints: %s", endpoints)
    return endpoints


FPP_API_ENDPOINTS = build_fpp_api_endpoints()


# ---------------------------------------------------------------------------
# MQTT Configuration and Client
# ---------------------------------------------------------------------------

def _read_mqtt_settings(settings_path: str) -> dict:
    """Parse FPP settings file to extract MQTT broker configuration."""
    mqtt_config = {
        'enabled': False,
        'broker': 'localhost',
        'port': 1883,
        'username': None,
        'password': None,
        'topic_prefix': 'FPP'
    }
    
    if not settings_path or not os.path.isfile(settings_path):
        return mqtt_config
    
    try:
        with open(settings_path, 'r', encoding='utf-8', errors='ignore') as fh:
            for line in fh:
                line = line.strip()
                if line.startswith('MQTTEnabled='):
                    mqtt_config['enabled'] = line.split('=', 1)[1].strip().lower() in ('1', 'true', 'yes')
                elif line.startswith('MQTTHost='):
                    mqtt_config['broker'] = line.split('=', 1)[1].strip()
                elif line.startswith('MQTTPort='):
                    try:
                        mqtt_config['port'] = int(line.split('=', 1)[1].strip())
                    except ValueError:
                        pass
                elif line.startswith('MQTTUsername='):
                    mqtt_config['username'] = line.split('=', 1)[1].strip()
                elif line.startswith('MQTTPassword='):
                    mqtt_config['password'] = line.split('=', 1)[1].strip()
                elif line.startswith('MQTTPrefix='):
                    prefix = line.split('=', 1)[1].strip()
                    if prefix:
                        mqtt_config['topic_prefix'] = prefix
    except Exception as err:
        logger.debug("Unable to parse MQTT settings from %s: %s", settings_path, err)
    
    return mqtt_config


def get_mqtt_config() -> dict:
    """Get MQTT configuration from FPP settings or environment."""
    # Check environment variables first
    mqtt_config = {
        'enabled': os.environ.get('FPP_MQTT_ENABLED', '').lower() in ('1', 'true', 'yes'),
        'broker': os.environ.get('FPP_MQTT_BROKER', 'localhost'),
        'port': int(os.environ.get('FPP_MQTT_PORT', '1883')),
        'username': os.environ.get('FPP_MQTT_USERNAME'),
        'password': os.environ.get('FPP_MQTT_PASSWORD'),
        'topic_prefix': os.environ.get('FPP_MQTT_PREFIX', 'FPP')
    }
    
    # If not enabled via env, check FPP settings files
    if not mqtt_config['enabled']:
        for path in _candidate_settings_paths():
            file_config = _read_mqtt_settings(path)
            if file_config['enabled']:
                mqtt_config.update(file_config)
                break
    
    # Default to enabled if broker is set (assume MQTT is available)
    if not mqtt_config['enabled'] and mqtt_config['broker']:
        mqtt_config['enabled'] = True
    
    return mqtt_config


class FPPMQTTClient:
    """MQTT client for controlling FPP via MQTT."""
    
    def __init__(self, config: dict):
        self.config = config
        self.client = None
        self.connected = False
        self.topic_prefix = config.get('topic_prefix', 'FPP')
        
    def connect(self):
        """Connect to MQTT broker."""
        if not MQTT_AVAILABLE:
            logger.error("MQTT not available - paho-mqtt not installed")
            return False
        
        if not self.config.get('enabled'):
            logger.warning("MQTT not enabled in configuration")
            return False
        
        try:
            self.client = mqtt.Client(client_id=f"fpp-homekit-{os.getpid()}")
            
            if self.config.get('username'):
                self.client.username_pw_set(
                    self.config['username'],
                    self.config.get('password')
                )
            
            self.client.on_connect = self._on_connect
            self.client.on_disconnect = self._on_disconnect
            
            broker = self.config.get('broker', 'localhost')
            port = self.config.get('port', 1883)
            
            logger.info(f"Connecting to MQTT broker at {broker}:{port}")
            self.client.connect(broker, port, keepalive=60)
            self.client.loop_start()
            
            # Wait for connection (max 5 seconds)
            for _ in range(50):
                if self.connected:
                    logger.info("MQTT connected successfully")
                    return True
                time.sleep(0.1)
            
            logger.warning("MQTT connection timeout")
            return False
            
        except Exception as e:
            logger.error(f"Failed to connect to MQTT broker: {e}")
            return False
    
    def _on_connect(self, client, userdata, flags, rc):
        """MQTT connection callback."""
        if rc == 0:
            self.connected = True
            logger.info("MQTT broker connected")
        else:
            logger.error(f"MQTT connection failed with code {rc}")
            self.connected = False
    
    def _on_disconnect(self, client, userdata, rc):
        """MQTT disconnection callback."""
        self.connected = False
        if rc != 0:
            logger.warning(f"MQTT disconnected unexpectedly (code {rc})")
    
    def publish_command(self, command: str, payload: str = ''):
        """Publish a command to FPP via MQTT."""
        if not self.connected or not self.client:
            logger.error("MQTT not connected, cannot publish command")
            return False
        
        topic = f"{self.topic_prefix}/command/{command}"
        try:
            result = self.client.publish(topic, payload, qos=1)
            if result.rc == mqtt.MQTT_ERR_SUCCESS:
                logger.info(f"Published MQTT command: {topic} = {payload}")
                return True
            else:
                logger.error(f"Failed to publish MQTT command: {topic} (rc={result.rc})")
                return False
        except Exception as e:
            logger.error(f"Error publishing MQTT command: {e}")
            return False
    
    def disconnect(self):
        """Disconnect from MQTT broker."""
        if self.client:
            self.client.loop_stop()
            self.client.disconnect()
            self.connected = False


def _request_with_fallback(method: str, path: str, **kwargs):
    """Attempt an HTTP request against the list of candidate endpoints."""
    last_exception = None
    last_response = None
    for base in FPP_API_ENDPOINTS:
        url = f'{base}{path}'
        try:
            response = requests.request(method, url, timeout=kwargs.pop('timeout', 5), **kwargs)
            if response.status_code == 200:
                return response
            last_response = response
            logger.debug("FPP API %s %s returned %s", method, url, response.status_code)
        except Exception as exc:
            last_exception = exc
            logger.debug("FPP API %s %s failed: %s", method, url, exc)
    if last_response is not None:
        return last_response
    if last_exception:
        raise last_exception
    raise RuntimeError("Unable to contact FPP API")

class FPPLightAccessory(Accessory):
    """HomeKit Light accessory that controls FPP playlists"""
    
    category = CATEGORY_LIGHTBULB
    
    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        
        # Load configuration
        self.config = self.load_config()
        self.playlist_name = self.config.get('playlist_name', '')
        
        # Initialize MQTT client
        mqtt_config = get_mqtt_config()
        self.mqtt_client = FPPMQTTClient(mqtt_config)
        self.use_mqtt = mqtt_config.get('enabled', False) and MQTT_AVAILABLE
        
        if self.use_mqtt:
            if not self.mqtt_client.connect():
                logger.warning("MQTT connection failed, falling back to HTTP API")
                self.use_mqtt = False
        else:
            logger.info("Using HTTP API for FPP control (MQTT not enabled or unavailable)")
        
        # Add Light service with On characteristic
        service = self.add_preload_service('Lightbulb')
        self.on_char = service.configure_char('On', value=False, setter_callback=self.set_on)
        
        # State tracking
        self.is_on = False
        self.fpp_status_polling = True
        
        # Start status polling thread
        self.poll_thread = threading.Thread(target=self.poll_fpp_status, daemon=True)
        self.poll_thread.start()
        
        logger.info(f"FPP-Controller Accessory initialized with playlist: {self.playlist_name} (using {'MQTT' if self.use_mqtt else 'HTTP'})")
    
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
        
        if self.use_mqtt and self.mqtt_client.connected:
            # Use MQTT to start playlist
            success = self.mqtt_client.publish_command(f"StartPlaylist/{self.playlist_name}")
            if success:
                logger.info(f"Started playlist via MQTT: {self.playlist_name}")
                return
            else:
                logger.warning("MQTT command failed, falling back to HTTP")
        
        # Fallback to HTTP API
        try:
            response = _request_with_fallback('POST', f'/playlists/{self.playlist_name}/start', timeout=5)
            if response.status_code == 200:
                logger.info(f"Started playlist via HTTP: {self.playlist_name}")
            else:
                logger.error(f"Failed to start playlist: {response.status_code} - {response.text}")
        except Exception as e:
            logger.error(f"Error starting playlist: {e}")
    
    def stop_playlist(self):
        """Stop FPP playback"""
        if self.use_mqtt and self.mqtt_client.connected:
            # Use MQTT to stop playback
            success = self.mqtt_client.publish_command("Stop")
            if success:
                logger.info("Stopped FPP playback via MQTT")
                return
            else:
                logger.warning("MQTT command failed, falling back to HTTP")
        
        # Fallback to HTTP API
        try:
            response = _request_with_fallback('POST', '/command/Stop', timeout=5)
            if response.status_code == 200:
                logger.info("Stopped FPP playback via HTTP")
            else:
                logger.error(f"Failed to stop playback: {response.status_code} - {response.text}")
        except Exception as e:
            logger.error(f"Error stopping playback: {e}")
    
    def get_fpp_status(self):
        """Get current FPP playback status"""
        try:
            response = _request_with_fallback('GET', '/status', timeout=5)
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
        if hasattr(self, 'mqtt_client') and self.mqtt_client:
            self.mqtt_client.disconnect()
        super().stop()

def get_accessory(driver):
    """Create and return the FPP Controller accessory"""
    return FPPLightAccessory(driver, 'FPP-Controller')

def main():
    """Main entry point"""
    try:
        # Create driver first
        state_file = os.path.join(PLUGIN_DIR, 'scripts', 'homekit_accessory.state')
        logger.info(f"Creating HomeKit driver (state file: {state_file})")
        driver = AccessoryDriver(port=51826, persist_file=state_file)
        
        # Add accessory
        logger.info("Adding FPP-Controller accessory...")
        accessory = get_accessory(driver)
        driver.add_accessory(accessory=accessory)
        
        # Save setup code and setup ID for QR code generation
        try:
            def _as_str(value, default=''):
                if isinstance(value, bytes):
                    try:
                        return value.decode()
                    except Exception:
                        return value.decode('utf-8', errors='ignore')
                return value if isinstance(value, str) else default

            setup_code = _as_str(driver.state.pincode, '000-00-000')
            setup_id = _as_str(getattr(driver.state, 'setup_id', ''), 'HOME')
            mac = _as_str(getattr(driver.state, 'mac', ''), '')
            
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
        
        # Write PID file only after successful initialization
        try:
            with open(PID_FILE, 'w') as f:
                f.write(str(os.getpid()))
            logger.info(f"PID file written: {PID_FILE}")
        except Exception as e:
            logger.error(f"Error writing PID file: {e}")
            raise
        
        # Start the driver
        logger.info("Starting HomeKit service...")

        try:
            signal.signal(signal.SIGTERM, driver.signal_handler)
            signal.signal(signal.SIGINT, driver.signal_handler)
            logger.debug("Signal handlers registered.")
        except Exception as e:
            logger.warning(f"Unable to register signal handlers: {e}")

        try:
            logger.info("HomeKit service running. Waiting for HomeKit events.")
            driver.start()
        except KeyboardInterrupt:
            logger.info("Keyboard interrupt received, stopping HomeKit service...")
        finally:
            driver.stop()
            logger.info("HomeKit service stopped")
            # Remove PID file
            try:
                if os.path.exists(PID_FILE):
                    os.remove(PID_FILE)
                    logger.info("PID file removed")
            except Exception as e:
                logger.error(f"Error removing PID file: {e}")
    except Exception as e:
        logger.error(f"Fatal error starting HomeKit service: {e}", exc_info=True)
        # Remove PID file if it exists
        try:
            if os.path.exists(PID_FILE):
                os.remove(PID_FILE)
        except:
            pass
        sys.exit(1)

if __name__ == '__main__':
    main()

