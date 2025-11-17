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
            # Subscribe to status topics specifically, plus wildcard to catch all FPP messages
            prefixes = {prefix, prefix.lower()}
            status_topics = []
            for base in prefixes:
                if not base:
                    continue
                normalized = base.rstrip('/')
                status_topics.extend([
                    f"{normalized}/status",
                    f"{normalized}/status/#",
                    f"{normalized}/#",  # Subscribe to all topics under prefix
                ])
            
            # Also subscribe to default topics if no prefix provided
            if not status_topics:
                status_topics = ['FPP/status', 'FPP/status/#', 'FPP/#', 'fpp/status', 'fpp/status/#', 'fpp/#']
            
            for topic in status_topics:
                client.subscribe(topic, qos=1)

            # Request status updates
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

            if not request_topics:
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
        # Process messages from FPP topics (status, playlist status, etc.)
        topic = msg.topic.lower()
        
        # Check if this is an FPP-related topic
        is_fpp_topic = False
        prefixes_lower = {prefix.lower(), 'fpp'}
        for pfx in prefixes_lower:
            if pfx and topic.startswith(pfx.lower() + '/'):
                is_fpp_topic = True
                break
        
        if not is_fpp_topic:
            return
        
        # Only process status-related topics or any JSON that looks like status
        is_status_topic = (
            '/status' in topic or 
            topic.endswith('/status') or 
            '/playliststatus' in topic or
            topic.endswith('/playliststatus')
        )
        
        try:
            payload = msg.payload.decode("utf-8")
            if not payload:
                return
            
            # Try to parse as JSON
            data = json.loads(payload)
            
            # Verify this looks like FPP status data
            # Accept if it has status-related fields OR if it's from a status topic
            if is_status_topic or 'status' in data or 'status_name' in data or 'current_playlist' in data:
                status_data = {
                    "available": True,
                    "timeout": False,
                    "status_name": data.get("status_name", "unknown"),
                    "status": data.get("status", 0),
                    "current_playlist": data.get("current_playlist", {}).get(
                        "playlist", ""
                    )
                    if isinstance(data.get("current_playlist"), dict)
                    else data.get("current_playlist", ""),
                    "current_sequence": data.get("current_sequence", ""),
                    "seconds_played": data.get("seconds_played", 0),
                    "seconds_remaining": data.get("seconds_remaining", 0),
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
