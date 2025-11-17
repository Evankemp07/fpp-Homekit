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

    def on_connect(client, userdata, flags, rc, *args, **kwargs):  # type: ignore
        if rc == 0:
            client.subscribe(f"{prefix}/status")
            # Request status updates explicitly in case FPP isn't broadcasting yet
            client.publish(f"{prefix}/command/GetStatus", "")
            client.publish(f"{prefix}/command/GetPlaylistStatus", "")

    def on_message(client, userdata, msg):  # type: ignore
        nonlocal status_data
        try:
            data = json.loads(msg.payload.decode("utf-8"))
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
        except Exception:
            # Ignore malformed payloads
            pass

    try:
        try:
            client = mqtt.Client(
                client_id="fpp-hk-status",
                callback_api_version=mqtt.CallbackAPIVersion.VERSION2,
            )
        except AttributeError:
            client = mqtt.Client(client_id="fpp-hk-status")

        client.on_connect = on_connect
        client.on_message = on_message
        client.connect(broker, port, keepalive=5)
        client.loop_start()

        for _ in range(50):  # wait up to ~5 seconds
            if status_data.get("available"):
                break
            time.sleep(0.1)

        client.loop_stop()
        client.disconnect()
        print(json.dumps(status_data))
    except Exception as exc:
        print(json.dumps({"error": str(exc), "available": False}))


if __name__ == "__main__":
    main()
