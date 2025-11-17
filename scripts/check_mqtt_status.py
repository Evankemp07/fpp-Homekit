#!/usr/bin/env python3

"""Helper script to check FPP MQTT status.

Outputs a JSON object describing status availability or error details.
This avoids complex quoting when invoking Python from PHP.
"""

import json
import sys
import time


def main() -> None:
    try:
        import paho.mqtt.client as mqtt  # type: ignore
    except ImportError:
        error_msg = (
            "paho-mqtt not installed. Install with: python3 -m pip install"
            " paho-mqtt --user"
        )
        print(
            json.dumps(
                {
                    "error": error_msg,
                    "available": False,
                    "install_command": "python3 -m pip install paho-mqtt --user",
                }
            )
        )
        return

    broker = sys.argv[1] if len(sys.argv) > 1 else "localhost"
    try:
        port = int(sys.argv[2]) if len(sys.argv) > 2 else 1883
    except ValueError:
        port = 1883
    prefix = sys.argv[3] if len(sys.argv) > 3 else "FPP"

    status_data = {"available": False, "timeout": True}
    connection_established = False
    connection_error = None

    def on_connect(client, userdata, flags, rc, *args, **kwargs):  # type: ignore
        nonlocal connection_established, connection_error
        if rc == 0:
            connection_established = True
            # Subscribe to all topics to catch FPP status messages regardless of topic structure
            # FPP can publish to various topic patterns like:
            # - FPP/status
            # - falcon/player/FPP2/falcon/player/FPP2/fppd_status
            # - {prefix}/status
            # - {prefix}/fppd_status
            # So we subscribe to everything and filter by content
            client.subscribe('#', qos=1)
            
            # Also subscribe to specific patterns based on prefix
            prefixes = {prefix, prefix.lower()}
            status_topics = []
            for base in prefixes:
                if not base:
                    continue
                normalized = base.rstrip('/')
                status_topics.extend([
                    f"{normalized}/status",
                    f"{normalized}/status/#",
                    f"{normalized}/fppd_status",
                    f"{normalized}/fppd_status/#",
                    f"{normalized}/#",  # Subscribe to all topics under prefix
                ])
            
            # Also subscribe to default FPP topics
            status_topics.extend([
                'FPP/status', 'FPP/status/#', 'FPP/fppd_status', 'FPP/fppd_status/#', 'FPP/#',
                'fpp/status', 'fpp/status/#', 'fpp/fppd_status', 'fpp/fppd_status/#', 'fpp/#',
                'falcon/#',  # Common FPP topic prefix
            ])
            
            # Remove duplicates while preserving order
            seen = set()
            unique_topics = []
            for topic in status_topics:
                if topic not in seen:
                    seen.add(topic)
                    unique_topics.append(topic)
            
            for topic in unique_topics:
                client.subscribe(topic, qos=1)

            # Request status updates using various topic patterns
            request_topics = set()
            for base in prefixes:
                if not base:
                    continue
                normalized = base.rstrip('/')
                request_topics.update(
                    {
                        f"{normalized}/command/GetStatus",
                        f"{normalized}/command/GetPlaylistStatus",
                    }
                )

            # Add default FPP command topics
            request_topics.update(
                {
                    'FPP/command/GetStatus',
                    'FPP/command/GetPlaylistStatus',
                    'fpp/command/GetStatus',
                    'fpp/command/GetPlaylistStatus',
                }
            )

            for topic in request_topics:
                client.publish(topic, '', qos=1)
        else:
            # Connection failed
            error_codes = {
                1: "Connection refused - incorrect protocol version",
                2: "Connection refused - invalid client identifier",
                3: "Connection refused - server unavailable",
                4: "Connection refused - bad username or password",
                5: "Connection refused - not authorised",
            }
            connection_error = error_codes.get(rc, f"Connection failed with code {rc}")

    def on_message(client, userdata, msg):  # type: ignore
        nonlocal status_data
        # Process ALL messages and filter by content - FPP can publish to various topic structures
        topic = msg.topic
        
        try:
            payload = msg.payload.decode("utf-8")
            if not payload or not payload.strip():
                return
            
            # Try to parse as JSON
            data = json.loads(payload)
            
            # Check if this looks like FPP status data by examining the JSON structure
            # FPP status messages typically have these fields:
            # - status_name (e.g., "idle", "playing", "paused")
            # - status (numeric: 0=idle, 1=playing, 2=paused, etc.)
            # - current_playlist (dict or string)
            # - fppd (e.g., "running")
            # - mode_name (e.g., "player")
            
            has_status_fields = (
                'status_name' in data or 
                ('status' in data and isinstance(data.get('status'), (int, str))) or
                'fppd' in data or
                'mode_name' in data
            )
            
            # Also check topic for status indicators (fppd_status, status, etc.)
            topic_lower = topic.lower()
            is_status_topic = (
                'fppd_status' in topic_lower or
                '/status' in topic_lower or
                topic_lower.endswith('/status') or
                '/playliststatus' in topic_lower
            )
            
            # Accept if it has FPP status fields OR is from a status topic
            if has_status_fields or is_status_topic:
                # Extract status information
                status_name = data.get("status_name", "unknown")
                status_val = data.get("status", 0)
                
                # Handle status as string or int
                if isinstance(status_val, str):
                    try:
                        status_val = int(status_val)
                    except (ValueError, TypeError):
                        status_val = 0
                
                # Extract playlist info
                current_playlist = ""
                playlist_data = data.get("current_playlist", {})
                if isinstance(playlist_data, dict):
                    current_playlist = playlist_data.get("playlist", "")
                elif isinstance(playlist_data, str):
                    current_playlist = playlist_data
                
                # Extract time info
                seconds_played = data.get("seconds_played", 0)
                if isinstance(seconds_played, str):
                    try:
                        seconds_played = int(float(seconds_played))
                    except (ValueError, TypeError):
                        seconds_played = 0
                
                seconds_remaining = data.get("seconds_remaining", 0)
                if isinstance(seconds_remaining, str):
                    try:
                        seconds_remaining = int(float(seconds_remaining))
                    except (ValueError, TypeError):
                        seconds_remaining = 0
                
                status_data = {
                    "available": True,
                    "timeout": False,
                    "status_name": status_name,
                    "status": status_val,
                    "current_playlist": current_playlist,
                    "current_sequence": data.get("current_sequence", ""),
                    "seconds_played": seconds_played,
                    "seconds_remaining": seconds_remaining,
                }
        except json.JSONDecodeError:
            # Not JSON, ignore
            pass
        except Exception:
            # Ignore other errors
            pass

    def on_disconnect(client, userdata, rc, *args, **kwargs):  # type: ignore
        nonlocal connection_established
        connection_established = False

    try:
        try:
            client = mqtt.Client(
                client_id="fpp-hk-status-check",
                callback_api_version=mqtt.CallbackAPIVersion.VERSION2,
            )
        except AttributeError:
            client = mqtt.Client(client_id="fpp-hk-status-check")

        client.on_connect = on_connect
        client.on_message = on_message
        client.on_disconnect = on_disconnect
        
        # Set connection timeout
        client.connect(broker, port, keepalive=5)
        client.loop_start()

        # Wait for connection to be established (max 3 seconds)
        connection_wait = 0
        while not connection_established and connection_wait < 30:
            if connection_error:
                break
            time.sleep(0.1)
            connection_wait += 1

        if connection_error:
            client.loop_stop()
            client.disconnect()
            print(json.dumps({"error": connection_error, "available": False}))
            return

        if not connection_established:
            client.loop_stop()
            client.disconnect()
            print(json.dumps({
                "error": f"Connection timeout - could not connect to MQTT broker at {broker}:{port}",
                "available": False
            }))
            return

        # Wait for status response (up to 8 seconds after connection)
        for _ in range(80):
            if status_data.get("available"):
                break
            time.sleep(0.1)

        client.loop_stop()
        client.disconnect()
        print(json.dumps(status_data))
    except ConnectionRefusedError:
        print(json.dumps({
            "error": f"Connection refused - MQTT broker not running at {broker}:{port}",
            "available": False
        }))
    except Exception as exc:
        print(json.dumps({"error": str(exc), "available": False}))


if __name__ == "__main__":
    main()
