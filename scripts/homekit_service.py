#!/usr/bin/env python3
"""
FPP HomeKit Integration Service
Exposes FPP as a HomeKit Light accessory using HAP-python
"""

import os
import sys

# Auto-detect and use venv if available
PLUGIN_DIR = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
VENV_PYTHON = os.path.join(PLUGIN_DIR, 'venv', 'bin', 'python3')

if os.path.exists(VENV_PYTHON) and sys.executable != VENV_PYTHON:
    print(f"Re-launching script using venv: {VENV_PYTHON}")
    os.execv(VENV_PYTHON, [VENV_PYTHON] + sys.argv)

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

# Configure logging with more verbose output for HomeKit
logging.basicConfig(
    level=logging.DEBUG,  # Changed to DEBUG for more detailed logs
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s'
)
logger = logging.getLogger(__name__)

# Enable debug logging for HAP-python components
logging.getLogger('pyhap').setLevel(logging.DEBUG)
logging.getLogger('pyhap.accessory_driver').setLevel(logging.DEBUG)
logging.getLogger('pyhap.hap_protocol').setLevel(logging.DEBUG)
logging.getLogger('pyhap.characteristic').setLevel(logging.DEBUG)

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
    """Get MQTT configuration from plugin config, FPP settings, or environment."""
    # Default config
    mqtt_config = {
        'enabled': True,
        'broker': 'localhost',
        'port': 1883,
        'username': None,
        'password': None,
        'topic_prefix': 'FPP'
    }
    
    # First priority: Plugin config file (highest priority)
    plugin_config_file = os.path.join(PLUGIN_DIR, 'scripts', 'homekit_config.json')
    if os.path.exists(plugin_config_file):
        try:
            with open(plugin_config_file, 'r') as f:
                plugin_config = json.load(f)
                if 'mqtt' in plugin_config and isinstance(plugin_config['mqtt'], dict):
                    mqtt_overrides = plugin_config['mqtt']
                    if 'broker' in mqtt_overrides:
                        mqtt_config['broker'] = mqtt_overrides['broker']
                    if 'port' in mqtt_overrides:
                        try:
                            mqtt_config['port'] = int(mqtt_overrides['port'])
                        except (ValueError, TypeError):
                            pass
                    if 'username' in mqtt_overrides:
                        mqtt_config['username'] = mqtt_overrides['username']
                    if 'password' in mqtt_overrides:
                        mqtt_config['password'] = mqtt_overrides['password']
                    if 'topic_prefix' in mqtt_overrides:
                        mqtt_config['topic_prefix'] = mqtt_overrides['topic_prefix']
                    if 'enabled' in mqtt_overrides:
                        mqtt_config['enabled'] = bool(mqtt_overrides['enabled'])
                    logger.info(f"Loaded MQTT config from plugin config file")
        except Exception as e:
            logger.debug(f"Could not read MQTT config from plugin config: {e}")
    
    # Second priority: Environment variables
    if os.environ.get('FPP_MQTT_ENABLED'):
        mqtt_config['enabled'] = os.environ.get('FPP_MQTT_ENABLED', '').lower() in ('1', 'true', 'yes')
    if os.environ.get('FPP_MQTT_BROKER'):
        mqtt_config['broker'] = os.environ.get('FPP_MQTT_BROKER', 'localhost')
    if os.environ.get('FPP_MQTT_PORT'):
        try:
            mqtt_config['port'] = int(os.environ.get('FPP_MQTT_PORT', '1883'))
        except ValueError:
            pass
    if os.environ.get('FPP_MQTT_USERNAME'):
        mqtt_config['username'] = os.environ.get('FPP_MQTT_USERNAME')
    if os.environ.get('FPP_MQTT_PASSWORD'):
        mqtt_config['password'] = os.environ.get('FPP_MQTT_PASSWORD')
    if os.environ.get('FPP_MQTT_PREFIX'):
        mqtt_config['topic_prefix'] = os.environ.get('FPP_MQTT_PREFIX', 'FPP')
    
    # Check FPP settings files for MQTT configuration
    for path in _candidate_settings_paths():
        file_config = _read_mqtt_settings(path)
        # Update config if we found MQTT settings (even if not explicitly enabled)
        if file_config.get('broker') and file_config['broker'] != 'localhost':
            # Found explicit broker config, use it
            mqtt_config.update(file_config)
            logger.info(f"Found MQTT config in {path}: broker={file_config.get('broker')}, enabled={file_config.get('enabled')}")
            break
        elif file_config.get('enabled'):
            # MQTT explicitly enabled in settings
            mqtt_config.update(file_config)
            logger.info(f"MQTT enabled in {path}")
            break
    
    # If MQTT is not explicitly disabled and we have a broker, try to use it
    # This allows MQTT to work even if FPP settings don't explicitly enable it
    if not mqtt_config['enabled']:
        # Check if MQTT was explicitly disabled
        mqtt_disabled = False
        for path in _candidate_settings_paths():
            file_config = _read_mqtt_settings(path)
            if file_config.get('broker') and not file_config.get('enabled'):
                # Found settings file with MQTT disabled
                mqtt_disabled = True
                break
        
        # If not explicitly disabled, enable MQTT by default (FPP often has MQTT running)
        if not mqtt_disabled:
            mqtt_config['enabled'] = True
            logger.info("MQTT not explicitly configured, enabling by default (will attempt connection)")
    
    logger.info(f"MQTT config: enabled={mqtt_config['enabled']}, broker={mqtt_config['broker']}:{mqtt_config['port']}, prefix={mqtt_config['topic_prefix']}")
    return mqtt_config


class FPPMQTTClient:
    """MQTT client for controlling FPP via MQTT."""

    def __init__(self, config: dict, accessory=None):
        self.config = config
        self.client = None
        self.connected = False
        self.topic_prefix = config.get('topic_prefix', 'FPP')
        self.accessory = accessory  # Reference to HomeKit accessory for state updates
        
    def connect(self):
        """Connect to MQTT broker."""
        if not MQTT_AVAILABLE:
            logger.error("MQTT not available - paho-mqtt not installed. Install with: pip install paho-mqtt")
            return False
        
        if not self.config.get('enabled'):
            logger.warning("MQTT not enabled in configuration")
            return False
        
        broker = self.config.get('broker', 'localhost')
        port = self.config.get('port', 1883)
        
        loop_started = False
        try:
            # Create new client if needed (for reconnection)
            if self.client:
                try:
                    self.client.loop_stop()
                except:
                    pass
                self.client = None
            
            self.client = mqtt.Client(client_id=f"fpp-homekit-{os.getpid()}")
            
            if self.config.get('username'):
                self.client.username_pw_set(
                    self.config['username'],
                    self.config.get('password')
                )
            
            self.client.on_connect = self._on_connect
            self.client.on_disconnect = self._on_disconnect
            self.client.on_message = self._on_message
            
            logger.info(f"Attempting to connect to MQTT broker at {broker}:{port}...")
            self.client.connect(broker, port, keepalive=60)
            self.client.loop_start()
            loop_started = True
            
            # Wait for connection (max 5 seconds)
            for i in range(50):
                if self.connected:
                    logger.info(f"✓ MQTT connected successfully to {broker}:{port}")
                    return True
                time.sleep(0.1)
            
            # Connection timeout - stop the loop and clean up
            if loop_started:
                self.client.loop_stop()
            logger.warning(f"MQTT connection timeout after 5 seconds to {broker}:{port}")
            logger.warning("  - Check if MQTT broker is running")
            logger.warning("  - Verify broker address and port in FPP settings")
            logger.warning("  - Service will retry connection automatically")
            return False
            
        except ConnectionRefusedError:
            if loop_started and self.client:
                try:
                    self.client.loop_stop()
                except:
                    pass
            logger.error(f"MQTT broker refused connection at {broker}:{port}")
            logger.error("  - Broker may not be running or port is incorrect")
            logger.error("  - Service will retry connection automatically")
            return False
        except Exception as e:
            if loop_started and self.client:
                try:
                    self.client.loop_stop()
                except:
                    pass
            logger.error(f"Failed to connect to MQTT broker at {broker}:{port}: {e}")
            logger.error("  - Service will retry connection automatically")
            return False
    
    def _on_connect(self, client, userdata, flags, rc):
        """MQTT connection callback."""
        if rc == 0:
            self.connected = True
            logger.info("MQTT broker connected")
        else:
            logger.error(f"MQTT connection failed with code {rc}")
            self.connected = False

    def _on_message(self, client, userdata, msg):
        """Handle incoming MQTT messages."""
        logger.debug(f"MQTT callback triggered for topic: {msg.topic}")
        try:
            payload = msg.payload.decode('utf-8')
            logger.debug(f"MQTT message received on topic '{msg.topic}': {payload[:100]}...")

            # FPP status messages can be JSON or simple text
            if payload.startswith('{'):
                import json
                try:
                    status_data = json.loads(payload)
                    logger.info(f"Parsed JSON status data: {json.dumps(status_data, indent=2)}")
                except json.JSONDecodeError as e:
                    logger.error(f"Failed to parse JSON: {e}, payload: {payload[:200]}")
                    return

                # FPP uses numeric status codes: 0=idle, 1=playing, 2=paused, etc.
                status_code = status_data.get('status', 0)
                status_name = status_data.get('status_name', 'idle')

                # Also check for 'status' field that might be a string
                status_str = status_data.get('status')
                if isinstance(status_str, str):
                    status_name = status_str.lower()

                current_playlist = status_data.get('current_playlist', {})
                playlist_name = ''
                if isinstance(current_playlist, dict):
                    playlist_name = current_playlist.get('playlist', '')
                elif isinstance(current_playlist, str):
                    playlist_name = current_playlist

                current_sequence = status_data.get('current_sequence', '')

                logger.info(f"FPP status parsed: code={status_code}, name={status_name}, playlist={playlist_name}, sequence={current_sequence}")

                # More robust status detection for playlist end
                # Status code 1 = playing, 0 = idle, 2 = paused (treated as idle), etc.
                is_idle = (status_code == 0 or status_code == 2 or
                          status_name.lower() in ['idle', 'stopped', 'finished', 'paused'] or
                          'idle' in status_name.lower() or
                          'stopped' in status_name.lower() or
                          'finished' in status_name.lower() or
                          'paused' in status_name.lower())

                # Check if actively playing
                fpp_playing = (status_code == 1 or
                             status_name.lower() == 'playing' or
                             'playing' in status_name.lower() or
                             status_data.get('playing', False))

                # Override: if status indicates idle/stopped, definitely not playing
                if is_idle:
                    fpp_playing = False

                logger.info(f"Determined playing state: {fpp_playing} (code={status_code}, name='{status_name}')")
            else:
                # Simple text status
                status_name = payload.lower()
                fpp_playing = 'playing' in status_name or status_name == 'playing'
                logger.info(f"FPP status (text): '{status_name}', playing={fpp_playing}")

            # Always update HomeKit state to match FPP (even if same, to ensure sync)
            if self.accessory:
                logger.info(f"About to check status change: fpp_playing={fpp_playing}, accessory.is_on={self.accessory.is_on}")
                if fpp_playing != self.accessory.is_on:
                    logger.info(f"FPP status changed via MQTT: {status_name} (playing={fpp_playing}, was={self.accessory.is_on})")
                    old_state = self.accessory.is_on
                    self.accessory.is_on = fpp_playing
                    try:
                        self.accessory.on_char.set_value(fpp_playing)
                        logger.info(f"HomeKit light set to: {fpp_playing} (was {old_state})")
                        # Force notification to HomeKit clients
                        self.accessory.on_char.notify()
                        logger.info("HomeKit notification sent to clients")

                        # Update status file for real-time UI updates
                        self._update_status_file()
                    except Exception as e:
                        logger.error(f"Failed to update HomeKit light state: {e}")
                        import traceback
                        logger.error(traceback.format_exc())
                else:
                    logger.debug(f"FPP status unchanged: {status_name} (playing={fpp_playing}, HomeKit already={self.accessory.is_on})")
            else:
                logger.warning("No accessory reference - cannot update HomeKit state")
        except Exception as e:
            logger.error(f"Error processing MQTT status message: {e}")
            import traceback
            logger.error(traceback.format_exc())

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

        prefix = (self.topic_prefix or '').strip('/')

        # Build a list of prefix variations to maximize compatibility with different FPP MQTT configurations
        prefix_candidates = []

        def add_candidate(candidate: str):
            normalized = (candidate or '').strip('/')
            if normalized not in prefix_candidates:
                prefix_candidates.append(normalized)

        if prefix:
            add_candidate(prefix)

        # Common fallbacks used across FPP installs
        for fallback_prefix in (
            'falcon/player/FPP2',
            'falcon/player/FPP',
            'FPP',
            'fpp',
        ):
            add_candidate(fallback_prefix)

        if '' not in prefix_candidates:
            prefix_candidates.append('')

        topics_to_try = []

        def add_topic(base_prefix: str, suffix: str):
            suffix = suffix.strip('/')
            if not suffix:
                return
            topic = f"{base_prefix}/{suffix}".strip('/') if base_prefix else suffix
            topic = topic.replace('//', '/')
            if topic not in topics_to_try:
                topics_to_try.append(topic)

        if command.startswith("StartPlaylist/"):
            playlist_name = command.split("/", 1)[1]
            for base in prefix_candidates:
                add_topic(base, f"set/playlist/{playlist_name}/start")
                add_topic(base, f"command/StartPlaylist/{playlist_name}")
                add_topic(base, f"set/command/StartPlaylist/{playlist_name}")
        elif command == "Stop":
            # For stop, we need the current playlist name - get it from config for now
            import os
            config_file = os.path.join(os.path.dirname(os.path.dirname(__file__)), 'homekit_config.json')
            current_playlist = 'Christmas-2024'  # Default fallback
            try:
                with open(config_file, 'r') as f:
                    import json
                    config = json.load(f)
                    current_playlist = config.get('playlist_name', 'Christmas-2024')
            except:
                pass

            for base in prefix_candidates:
                add_topic(base, f"set/playlist/{current_playlist}/stop/now")
                add_topic(base, f"set/playlist/{current_playlist}/stop")
                add_topic(base, "command/Stop")
                add_topic(base, "set/command/Stop")
        else:
            for base in prefix_candidates:
                add_topic(base, f"set/command/{command}")
                add_topic(base, f"command/{command}")

        success = False
        for topic in topics_to_try:
            try:
                # Use QoS 0 for faster delivery (fire-and-forget)
                result = self.client.publish(topic, payload, qos=0)
                if result.rc == mqtt.MQTT_ERR_SUCCESS:
                    logger.debug(f"Published MQTT command: {topic} = {payload}")
                    success = True
                else:
                    logger.debug(f"Failed to publish to {topic} (rc={result.rc})")
            except Exception as e:
                logger.debug(f"Error publishing to {topic}: {e}")

        if not success:
            logger.error(f"Failed to publish MQTT command to any topic: {command}")
        return success
    
    def subscribe(self, topic: str, callback=None, qos=1):
        """Subscribe to an MQTT topic."""
        if not self.connected or not self.client:
            logger.error("MQTT not connected, cannot subscribe")
            return False

        try:
            self.client.subscribe(topic, qos=qos)
            if callback:
                self.client.message_callback_add(topic, callback)
            logger.info(f"Subscribed to MQTT topic: {topic}")
            return True
        except Exception as e:
            logger.error(f"Error subscribing to {topic}: {e}")
            return False
    
    def _update_status_file(self):
        """Update status file for real-time UI updates"""
        try:
            import json
            status_file = os.path.join(os.path.dirname(os.path.dirname(__file__)), 'homekit_status.json')

            # Get current status from accessory
            status_data = {
                'homekit_light_on': getattr(self.accessory, 'is_on', False),
                'service_running': True,  # We're running if we can update this
                'timestamp': int(time.time())
            }

            # Try to get MQTT connection status
            status_data['mqtt_connected'] = self.connected

            with open(status_file, 'w') as f:
                json.dump(status_data, f)

        except Exception as e:
            logger.debug(f"Could not update status file: {e}")

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
        
        # Initialize MQTT client (MQTT only, no HTTP fallback)
        if not MQTT_AVAILABLE:
            logger.warning("MQTT not available - paho-mqtt not installed. Install with: pip install paho-mqtt --user")
            logger.warning("HomeKit service will start but FPP control will not work until paho-mqtt is installed")
            self.mqtt_client = None
            self.use_mqtt = False
        else:
            mqtt_config = get_mqtt_config()
            self.mqtt_client = FPPMQTTClient(mqtt_config, self)
            self.use_mqtt = True
        
        # Try to connect to MQTT (will retry in background thread if fails)
        mqtt_connected = False
        if self.use_mqtt and self.mqtt_client:
            mqtt_connected = self.mqtt_client.connect()
            if not mqtt_connected:
                logger.warning("Initial MQTT connection failed, will retry in background thread")
                logger.warning("Service will start but control commands won't work until MQTT connects")
        else:
            logger.warning("MQTT not available - service will start but FPP control will not work")
        
        # Add Light service with On characteristic
        service = self.add_preload_service('Lightbulb')
        self.on_char = service.configure_char('On', value=False, setter_callback=self.set_on)
        
        # State tracking
        self.is_on = False
        self.fpp_status_polling = True
        
        # Subscribe to FPP status updates via MQTT (if connected)
        if self.use_mqtt and mqtt_connected and self.mqtt_client:
            self.subscribe_to_status()
        
        # Start MQTT polling thread (handles reconnections and subscriptions) - only if MQTT is available
        if self.use_mqtt and self.mqtt_client:
            self.poll_thread = threading.Thread(target=self.poll_fpp_status_mqtt, daemon=True)
            self.poll_thread.start()
        
        mqtt_status = "MQTT available" if self.use_mqtt else "MQTT not available (paho-mqtt not installed)"
        logger.info(f"FPP-Controller Accessory initialized with playlist: {self.playlist_name} ({mqtt_status}, connected={mqtt_connected})")
    
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
        """Handle HomeKit On/Off characteristic changes - optimized for speed"""
        logger.info(f"HomeKit set_on called with value: {value}")

        # Immediately update HomeKit state for snappy response
        self.is_on = value
        self.on_char.set_value(value)

        # Process MQTT commands in background for maximum speed
        import threading
        def process_command():
            try:
                # Track HomeKit command for UI display
                self._record_homekit_command("start" if value else "stop")

                # Reload config to get latest playlist name
                self.config = self.load_config()
                self.playlist_name = self.config.get('playlist_name', '')

                # Send MQTT command
                if value:
                    self.start_playlist()
                else:
                    self.stop_playlist()

                logger.debug(f"HomeKit command processed in background: {'start' if value else 'stop'}")
            except Exception as e:
                logger.error(f"Error processing HomeKit command in background: {e}")

        # Start background processing
        command_thread = threading.Thread(target=process_command, daemon=True)
        command_thread.start()

    def _record_homekit_command(self, action):
        """Record HomeKit command for UI display"""
        try:
            import json
            command_file = os.path.join(PLUGIN_DIR, 'scripts', 'homekit_commands.json')

            # Load existing commands
            commands = []
            if os.path.exists(command_file):
                try:
                    with open(command_file, 'r') as f:
                        commands = json.load(f)
                except:
                    commands = []

            # Add new command (keep only last 10)
            commands.append({
                'action': action,
                'timestamp': int(time.time()),
                'source': 'homekit'
            })
            commands = commands[-10:]  # Keep only last 10

            # Save
            with open(command_file, 'w') as f:
                json.dump(commands, f)

        except Exception as e:
            logger.debug(f"Could not record HomeKit command: {e}")
    
    def subscribe_to_status(self):
        """Subscribe to FPP status updates via MQTT."""
        if not self.use_mqtt or not self.mqtt_client:
            logger.warning("Cannot subscribe to MQTT status: paho-mqtt not installed")
            return
        
        # Subscribe only to needed FPP topics to reduce traffic
        topics_to_subscribe = [
            ('falcon/player/FPP2/status', 1),
            ('falcon/player/FPP2/falcon/player/FPP2/port_status', 1)
        ]

        subscribed_count = 0
        for topic, qos in topics_to_subscribe:
            if self.mqtt_client.subscribe(topic, qos=qos):
                subscribed_count += 1
                logger.debug(f"Subscribed to MQTT topic: {topic}")
            else:
                logger.error(f"Failed to subscribe to MQTT topic: {topic}")

        logger.info(f"Successfully subscribed to {subscribed_count}/{len(topics_to_subscribe)} MQTT topics")
        
        # Request initial status from FPP
        logger.info(f"Requesting initial FPP status via MQTT topic: {self.mqtt_client.topic_prefix}/command/GetStatus")
        self.mqtt_client.publish_command("GetStatus")

        # Also try requesting playlist status
        logger.info(f"Requesting playlist status via MQTT topic: {self.mqtt_client.topic_prefix}/command/GetPlaylistStatus")
        self.mqtt_client.publish_command("GetPlaylistStatus")

        # Sync initial HomeKit state with FPP after a short delay
        import threading
        def sync_initial_state():
            time.sleep(3)  # Wait for MQTT connection and initial responses
            try:
                # Query FPP status via HTTP API as fallback
                import requests
                response = requests.get("http://localhost/api/system/status", timeout=5)
                if response.status_code == 200:
                    status_data = response.json()
                    status_code = status_data.get('status', 0)
                    status_name = status_data.get('status_name', 'idle')
                    fpp_playing = status_code == 1 or status_name.lower() == 'playing' or 'playing' in status_name.lower()

                    logger.info(f"Initial FPP sync: status={status_code} ({status_name}), playing={fpp_playing}, current HomeKit={self.is_on}")
                    if fpp_playing != self.is_on:
                        logger.info(f"Syncing HomeKit light to match FPP: {fpp_playing}")
                        self.is_on = fpp_playing
                        self.on_char.set_value(fpp_playing)
                        self.on_char.notify()
                        logger.info("Initial HomeKit state synced with FPP")
                    else:
                        logger.info("HomeKit state already matches FPP")
                else:
                    logger.warning("Could not query FPP status for initial sync")
            except Exception as e:
                logger.error(f"Error syncing initial state: {e}")

        # Start initial sync in background
        sync_thread = threading.Thread(target=sync_initial_state, daemon=True)
        sync_thread.start()
    
    def start_playlist(self):
        """Start the configured FPP playlist via MQTT"""
        if not self.playlist_name:
            logger.warning("No playlist configured")
            return
        
        if not self.use_mqtt or not self.mqtt_client:
            logger.error(f"Cannot start playlist: paho-mqtt not installed. Install with: pip install paho-mqtt --user")
            logger.error(f"Playlist: {self.playlist_name}")
            return
        
        if not self.mqtt_client.connected:
            logger.error(f"Cannot start playlist: MQTT not connected. Playlist: {self.playlist_name}")
            logger.error("MQTT connection will be retried automatically")
            return
        
        logger.info(f"Starting playlist via MQTT: {self.playlist_name}")
        success = self.mqtt_client.publish_command(f"StartPlaylist/{self.playlist_name}")
        if not success:
            logger.error(f"Failed to start playlist via MQTT: {self.playlist_name}")
    
    def stop_playlist(self):
        """Stop FPP playback via MQTT"""
        if not self.use_mqtt or not self.mqtt_client:
            logger.error("Cannot stop playback: paho-mqtt not installed. Install with: pip install paho-mqtt --user")
            return
        
        if not self.mqtt_client.connected:
            logger.error("Cannot stop playback: MQTT not connected")
            logger.error("MQTT connection will be retried automatically")
            return
        
        logger.info("Stopping playback via MQTT")
        success = self.mqtt_client.publish_command("Stop")
        if not success:
            logger.error("Failed to stop playback via MQTT")
    
    def poll_fpp_status_mqtt(self):
        """Keep MQTT connection alive and handle reconnections - optimized"""
        if not self.use_mqtt or not self.mqtt_client:
            logger.warning("MQTT polling thread started but paho-mqtt not available")
            return

        subscriptions_setup = False
        last_status_request = 0
        reconnect_delay = 5  # Start with 5s, exponential backoff up to 60s

        while self.fpp_status_polling:
            try:
                # Check connection status
                if not self.mqtt_client.connected:
                    if subscriptions_setup:
                        logger.warning("MQTT disconnected, attempting reconnect...")
                    else:
                        logger.info("MQTT not connected, attempting initial connection...")

                    if self.mqtt_client.connect():
                        logger.info("MQTT connected successfully")
                        if not subscriptions_setup:
                            self.subscribe_to_status()
                            subscriptions_setup = True
                        reconnect_delay = 5  # Reset delay on successful connection
                    else:
                        logger.warning(f"MQTT connection failed, will retry in {reconnect_delay}s...")
                        time.sleep(reconnect_delay)
                        reconnect_delay = min(reconnect_delay * 2, 60)  # Exponential backoff, max 60s
                        continue
                elif not subscriptions_setup:
                    # Connected but subscriptions not set up yet
                    self.subscribe_to_status()
                    subscriptions_setup = True
                    logger.info("MQTT status subscriptions set up")

                # Only request status every 30 seconds (reduced frequency for performance)
                current_time = time.time()
                if current_time - last_status_request > 30:
                    try:
                        self.mqtt_client.publish_command("GetStatus")
                        last_status_request = current_time
                    except Exception as e:
                        logger.debug(f"Could not request status update: {e}")

                time.sleep(5)  # Check connection every 5 seconds (reduced frequency)
            except Exception as e:
                logger.error(f"Error in MQTT status polling: {e}")
                time.sleep(10)
    
    def stop(self):
        """Stop the accessory"""
        self.fpp_status_polling = False
        if hasattr(self, 'use_mqtt') and self.use_mqtt and hasattr(self, 'mqtt_client') and self.mqtt_client:
            self.mqtt_client.disconnect()
        super().stop()

def get_accessory(driver):
    """Create and return the FPP Controller accessory"""
    return FPPLightAccessory(driver, 'FPP-Controller')

def main():
    """Main entry point with crash protection"""
    try:
        # Set process priority to low to avoid interfering with FPP
        import os
        try:
            os.nice(10)  # Lower priority
        except:
            pass

        # Limit memory usage
        import resource
        try:
            # Set memory limit to 100MB
            resource.setrlimit(resource.RLIMIT_AS, (100 * 1024 * 1024, 150 * 1024 * 1024))
        except:
            pass
        # Create driver first
        state_file = os.path.join(PLUGIN_DIR, 'scripts', 'homekit_accessory.state')
        logger.info(f"Creating HomeKit driver (state file: {state_file})")
        
        # Validate existing state file if it exists
        if os.path.exists(state_file):
            try:
                with open(state_file, 'r') as f:
                    state_data = json.load(f)
                    pincode = state_data.get('pincode', '')
                    if pincode:
                        pincode_str = pincode.decode() if isinstance(pincode, bytes) else str(pincode)
                        pincode_clean = pincode_str.replace('-', '')
                        if len(pincode_clean) != 8 or not pincode_clean.isdigit():
                            logger.warning(f"Invalid setup code in state file: {pincode_str}, regenerating state file")
                            os.rename(state_file, state_file + '.backup')
                            logger.info("Backed up invalid state file, will create new one")
            except Exception as e:
                logger.warning(f"Could not validate state file: {e}")
        
        # Get network configuration
        homekit_ip = None
        if os.path.exists(CONFIG_FILE):
            try:
                with open(CONFIG_FILE, 'r') as f:
                    config = json.load(f)
                    homekit_ip = config.get('homekit_ip', None)
                    if homekit_ip:
                        logger.info(f"Using configured HomeKit IP: {homekit_ip}")
            except Exception as e:
                logger.warning(f"Could not read HomeKit IP from config: {e}")
        
        # If no IP configured, try to auto-detect the primary interface
        if not homekit_ip:
            try:
                import socket
                # Try to get the IP by connecting to an external address (doesn't actually connect)
                s = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
                s.connect(("8.8.8.8", 80))
                homekit_ip = s.getsockname()[0]
                s.close()
                logger.info(f"Auto-detected HomeKit IP: {homekit_ip}")
            except Exception as e:
                logger.warning(f"Could not auto-detect IP, using 0.0.0.0: {e}")
                homekit_ip = "0.0.0.0"  # Listen on all interfaces
        
        logger.info(f"HomeKit will listen on: {homekit_ip}:51826")
        
        driver = AccessoryDriver(
            port=51826, 
            persist_file=state_file,
            address=homekit_ip  # Bind to specific IP
        )
        
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

            setup_code = _as_str(driver.state.pincode, '0000-0000')
            mac = _as_str(getattr(driver.state, 'mac', ''), '')

            # Validate setup code format: XXXX-XXXX (display) = 8 digits (encoded)
            # HomeKit setup codes must be exactly 8 numeric digits when dashes are removed
            setup_code_clean = setup_code.replace('-', '')
            if len(setup_code_clean) != 8 or not setup_code_clean.isdigit():
                logger.error(f"Invalid setup code format: {setup_code} (expected format: XXXX-XXXX)")
                logger.error(f"This usually means the state file is corrupted. Please restart the service.")
            
            # Generate QR code data using HAP-python's method
            qr_code_data = None
            setup_id = None
            
            if QR_AVAILABLE:
                try:
                    # Get the accessory to extract its info
                    acc = driver.accessory
                    if acc:
                        logger.info(f"Accessory category: {acc.category}")
                        logger.info(f"Accessory MAC: {mac}")
                        
                        # HAP-python's qr.get_qr_code expects:
                        # - setup_code: the 8-digit PIN (with or without dashes)
                        # - setup_id: 4-character identifier
                        # But it can also derive setup_id from accessory category
                        # Try to get setup_id from state first
                        setup_id = _as_str(getattr(driver.state, 'setup_id', ''), '')
                        
                        # Clean setup_id - remove colons, take only alphanumeric
                        setup_id = ''.join(c for c in setup_id if c.isalnum())

                        if not setup_id or len(setup_id) != 4:
                            # Generate setup_id from MAC (standard approach - last 4 chars, no colons)
                            if mac:
                                # Remove ALL non-alphanumeric chars from MAC, then take last 4 chars
                                mac_clean = ''.join(c for c in mac if c.isalnum())
                                setup_id = mac_clean[-4:].upper() if len(mac_clean) >= 4 else 'HOME'
                            else:
                                setup_id = 'HOME'
                        
                        # Ensure it's uppercase and exactly 4 chars
                        setup_id = setup_id.upper()[:4].ljust(4, '0')
                        
                        logger.info(f"Using setup ID: {setup_id} (cleaned)")
                        
                        # Generate QR code with HAP-python's built-in function
                        qr_code_data = qr.get_qr_code(setup_code, setup_id)
                        logger.info(f"Generated QR code data: {qr_code_data[:60]}...")
                    else:
                        logger.warning("No accessory object available for QR generation")
                        # Fallback: Use MAC-based setup_id
                        setup_id = mac[-4:].upper() if mac and len(mac) >= 4 else 'HOME'
                        qr_code_data = qr.get_qr_code(setup_code, setup_id)
                        logger.info(f"Generated QR code data (fallback): {qr_code_data[:60]}...")
                    
                except Exception as e:
                    logger.warning(f"Could not generate QR code: {e}")
                    logger.warning(f"Setup code: {setup_code}")
                    import traceback
                    logger.warning(traceback.format_exc())
                    # Create a basic setup_id from MAC as last resort (remove non-alphanumeric)
                    if mac:
                        mac_clean = ''.join(c for c in mac if c.isalnum())
                        setup_id = mac_clean[-4:].upper() if len(mac_clean) >= 4 else 'HOME'
                    else:
                        setup_id = 'HOME'
            else:
                # No QR module, just generate setup_id from MAC (remove non-alphanumeric)
                if mac:
                    mac_clean = ''.join(c for c in mac if c.isalnum())
                    setup_id = mac_clean[-4:].upper() if len(mac_clean) >= 4 else 'HOME'
                else:
                    setup_id = 'HOME'
            
            # Save to a JSON file for PHP to read
            info_file = os.path.join(PLUGIN_DIR, 'scripts', 'homekit_pairing_info.json')
            info_data = {
                'setup_code': setup_code,
                'setup_id': setup_id if setup_id else 'UNKNOWN',
                'mac': mac
            }
            if qr_code_data:
                info_data['qr_data'] = qr_code_data
            
            with open(info_file, 'w') as f:
                json.dump(info_data, f, indent=2)
            
            logger.info(f"Setup code: {setup_code}, Setup ID: {setup_id}")
            logger.info(f"Pairing info saved to: {info_file}")
        except Exception as e:
            logger.warning(f"Could not save setup info: {e}")
            import traceback
            logger.warning(traceback.format_exc())
        
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

        # Register signal handlers for clean shutdown
        def signal_handler(signum, frame):
            logger.info(f"Received signal {signum}, shutting down gracefully...")
            try:
                driver.stop()
                logger.info("HomeKit service stopped cleanly")
            except Exception as e:
                logger.error(f"Error during shutdown: {e}")
            finally:
                sys.exit(0)

        try:
            signal.signal(signal.SIGTERM, signal_handler)
            signal.signal(signal.SIGINT, signal_handler)
            signal.signal(signal.SIGHUP, signal_handler)  # Handle terminal disconnect
            logger.debug("Signal handlers registered.")
        except Exception as e:
            logger.warning(f"Unable to register signal handlers: {e}")

        try:
            logger.info("HomeKit service running. Waiting for HomeKit events.")
            driver.start()
        except KeyboardInterrupt:
            logger.info("Keyboard interrupt received, stopping HomeKit service...")
        except RuntimeError as e:
            if "Event loop is closed" in str(e):
                logger.warning("Event loop closed during startup - this may be due to unclean shutdown of previous instance")
                logger.warning("Try waiting longer between restarts or check for zombie processes")
            else:
                logger.error(f"Runtime error during startup: {e}")
            raise
        except Exception as e:
            logger.error(f"Unexpected error during HomeKit service startup: {e}")
            raise
        finally:
            try:
                logger.info("Stopping HomeKit service...")
                driver.stop()
                logger.info("HomeKit service stopped cleanly")
            except Exception as e:
                logger.warning(f"Error during service shutdown: {e}")
                # Force cleanup even if stop() fails
                import asyncio
                try:
                    loop = asyncio.get_event_loop()
                    if not loop.is_closed():
                        loop.close()
                except:
                    pass
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
    try:
        main()
    except Exception as e:
        logger.critical(f"Fatal error in main(): {e}", exc_info=True)
        # Ensure we don't crash the system - exit gracefully
        try:
            import sys
            sys.exit(1)
        except:
            os._exit(1)  # Last resort exit

