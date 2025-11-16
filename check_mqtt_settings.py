#!/usr/bin/env python3
"""Quick script to check MQTT settings that the plugin will use"""

import os
import json

script_dir = os.path.dirname(os.path.abspath(__file__))
scripts_dir = os.path.join(script_dir, 'scripts')
config_file = os.path.join(scripts_dir, 'homekit_config.json')

# Default config
mqtt_config = {
    'broker': 'localhost',
    'port': 1883,
    'topic_prefix': 'FPP',
    'username': None,
    'password': None,
    'enabled': True
}

# Read plugin config
if os.path.exists(config_file):
    try:
        with open(config_file, 'r') as f:
            plugin_config = json.load(f)
            if 'mqtt' in plugin_config:
                mqtt_config.update(plugin_config['mqtt'])
    except Exception as e:
        print(f"Note: Could not read plugin config: {e}")

# Read FPP settings files
settings_paths = [
    '/home/fpp/media/settings',
    '/opt/fpp/media/settings'
]

fpp_settings_found = []
for path in settings_paths:
    if os.path.exists(path):
        fpp_settings_found.append(path)
        try:
            with open(path, 'r') as f:
                for line in f:
                    line = line.strip()
                    if line.startswith('MQTTEnabled='):
                        mqtt_config['enabled'] = line.split('=', 1)[1].strip().lower() in ('1', 'true', 'yes')
                    elif line.startswith('MQTTHost='):
                        mqtt_config['broker'] = line.split('=', 1)[1].strip()
                    elif line.startswith('MQTTPort='):
                        try:
                            mqtt_config['port'] = int(line.split('=', 1)[1].strip())
                        except:
                            pass
                    elif line.startswith('MQTTUsername='):
                        mqtt_config['username'] = line.split('=', 1)[1].strip()
                    elif line.startswith('MQTTPassword='):
                        mqtt_config['password'] = line.split('=', 1)[1].strip()
                    elif line.startswith('MQTTPrefix='):
                        prefix = line.split('=', 1)[1].strip()
                        if prefix:
                            mqtt_config['topic_prefix'] = prefix
        except Exception as e:
            pass

print("=" * 60)
print("MQTT SETTINGS THE PLUGIN WILL USE:")
print("=" * 60)
print(f"Broker:        {mqtt_config['broker']}")
print(f"Port:          {mqtt_config['port']}")
print(f"Topic Prefix:  {mqtt_config['topic_prefix']}")
print(f"Username:      {mqtt_config['username'] or '(not set)'}")
print(f"Password:      {'***' if mqtt_config['password'] else '(not set)'}")
print(f"Enabled:       {mqtt_config['enabled']}")
print()
print("TOPICS THAT WILL BE USED:")
print("=" * 60)
prefix = mqtt_config['topic_prefix']
print(f"Start Playlist: {prefix}/command/StartPlaylist/{{playlist_name}}")
print(f"Stop:           {prefix}/command/Stop")
print(f"Status:         {prefix}/status")
print(f"Playlist Status: {prefix}/playlist/status")
print()
print("VERIFICATION:")
print("=" * 60)
broker = mqtt_config['broker']
port = mqtt_config['port']

if broker == 'localhost':
    print("⚠️  WARNING: Broker is set to 'localhost'")
    print("   If your MQTT broker is on a different host, update:")
    print("   - FPP Settings: MQTTHost=your-broker-ip")
    print("   - Or plugin config: homekit_config.json")
else:
    print(f"✓ Broker is set to: {broker}")

if port == 1883:
    print(f"✓ Using standard MQTT port: {port}")
else:
    print(f"✓ Using custom port: {port}")

if mqtt_config['enabled']:
    print("✓ MQTT is enabled")
else:
    print("⚠️  WARNING: MQTT is disabled in settings")

if fpp_settings_found:
    print(f"✓ Found FPP settings file: {fpp_settings_found[0]}")
else:
    print("⚠️  WARNING: No FPP settings file found")
    print("   Plugin will use defaults (localhost:1883)")

print()
print("WHAT YOU NEED IN FPP MQTT SETTINGS:")
print("=" * 60)
print("✓ Broker Host: Set to your MQTT broker IP/hostname")
print("✓ Broker Port: Usually 1883 (or 8883 for SSL)")
print("✓ Topic Prefix: Should be 'FPP' (default)")
print("✓ Username/Password: Only if your broker requires auth")
print()
print("The plugin will automatically read these from FPP settings!")
