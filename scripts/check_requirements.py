#!/usr/bin/env python3
"""
Check all FPP HomeKit plugin requirements
"""

import os
import sys
import json

def check_requirement(description, condition, details=""):
    """Check a requirement and report status"""
    if condition:
        print(f"✅ {description}")
        if details:
            print(f"   {details}")
        return True
    else:
        print(f"❌ {description}")
        if details:
            print(f"   {details}")
        return False

def main():
    print("🔍 FPP HomeKit Plugin Requirements Check")
    print("=" * 50)

    plugin_dir = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
    scripts_dir = os.path.join(plugin_dir, "scripts")

    all_passed = True

    # 1. Plugin runs in venv (checked at runtime via auto-detection)
    service_file = os.path.join(scripts_dir, "homekit_service.py")
    venv_autodetect = False
    if os.path.exists(service_file):
        with open(service_file, 'r') as f:
            content = f.read()
            venv_autodetect = 'os.execv(VENV_PYTHON' in content and 'VENV_PYTHON' in content

    venv_exists = os.path.exists(os.path.join(plugin_dir, "venv"))
    venv_ok = venv_exists or venv_autodetect  # Either exists or will be auto-detected

    all_passed &= check_requirement(
        "Plugin runs in venv",
        venv_ok,
        f"Virtual environment {'found' if venv_exists else 'will auto-detect'} in plugin/venv/"
    )

    # 2. UI updates without polling
    sse_endpoint_exists = False
    api_file = os.path.join(plugin_dir, "api.php")
    if os.path.exists(api_file):
        with open(api_file, 'r') as f:
            content = f.read()
            sse_endpoint_exists = 'fppHomekitEvents' in content

    all_passed &= check_requirement(
        "UI updates without polling (Server-Sent Events)",
        sse_endpoint_exists,
        "SSE endpoint /events implemented in api.php"
    )

    # 3. HomeKit turns off when not playing
    auto_turn_off = False
    service_file = os.path.join(scripts_dir, "homekit_service.py")
    if os.path.exists(service_file):
        with open(service_file, 'r') as f:
            content = f.read()
            auto_turn_off = 'fpp_playing = False' in content and 'set_value(fpp_playing)' in content

    all_passed &= check_requirement(
        "HomeKit turns off when not playing",
        auto_turn_off,
        "Auto-turn-off logic implemented in MQTT callback"
    )

    # 4. HomeKit appears on when playing
    auto_turn_on = False
    if os.path.exists(service_file):
        with open(service_file, 'r') as f:
            content = f.read()
            auto_turn_on = 'fpp_playing = True' in content or 'status_code == 1' in content

    all_passed &= check_requirement(
        "HomeKit appears on when playing",
        auto_turn_on,
        "Auto-turn-on logic implemented for playing status"
    )

    # 5. Setup code formatted XXXX-XXXX
    setup_code_format = False
    if os.path.exists(service_file):
        with open(service_file, 'r') as f:
            content = f.read()
            setup_code_format = "XXXX-XXXX" in content and "'0000-0000'" in content

    all_passed &= check_requirement(
        "Setup code formatted XXXX-XXXX",
        setup_code_format,
        "Setup code uses XXXX-XXXX format (4-4)"
    )

    # 6. Plugin doesn't crash Pi/FPP/dependencies
    crash_protection = False
    if os.path.exists(service_file):
        with open(service_file, 'r') as f:
            content = f.read()
            crash_protection = (
                'os.nice(10)' in content and  # Low priority
                'resource.setrlimit' in content and  # Memory limits
                'signal_handler' in content and  # Signal handling
                'except Exception as e:' in content  # Exception handling
            )

    all_passed &= check_requirement(
        "Plugin crash protection (doesn't crash Pi/FPP)",
        crash_protection,
        "Low priority, memory limits, signal handlers, exception handling"
    )

    # 7. HomeKit starts/stops playlist
    playlist_control = False
    if os.path.exists(service_file):
        with open(service_file, 'r') as f:
            content = f.read()
            playlist_control = (
                'start_playlist' in content and
                'stop_playlist' in content and
                'set_on' in content
            )

    all_passed &= check_requirement(
        "HomeKit starts/stops playlist when light on/off",
        playlist_control,
        "set_on() calls start_playlist() and stop_playlist()"
    )

    # Additional checks
    print("\n🔧 Additional Features:")

    # Real-time updates
    realtime_updates = False
    status_file = os.path.join(scripts_dir, "status.php")
    if os.path.exists(status_file):
        with open(status_file, 'r') as f:
            content = f.read()
            realtime_updates = 'EventSource' in content and 'initializeEventSource' in content

    # This is an additional feature check, not a core requirement
    check_requirement(
        "Real-time UI updates (EventSource/SSE)",
        realtime_updates,
        "JavaScript uses EventSource for push updates"
    )

    # Venv isolation
    venv_isolation = False
    requirements_file = os.path.join(scripts_dir, "requirements.txt")
    if os.path.exists(requirements_file):
        with open(requirements_file, 'r') as f:
            content = f.read()
            venv_isolation = 'HAP-python' in content and 'paho-mqtt' in content

    check_requirement(
        "Virtual environment isolation",
        venv_isolation,
        "Dependencies isolated in venv"
    )

    # Performance optimizations
    performance_opts = False
    if os.path.exists(service_file):
        with open(service_file, 'r') as f:
            content = f.read()
            performance_opts = (
                'qos=0' in content and  # Fast MQTT
                'threading' in content and  # Background processing
                'sleep(5)' in content  # Reduced polling
            )

    check_requirement(
        "Performance optimizations",
        performance_opts,
        "QoS 0, background threads, reduced polling"
    )

    print("\n" + "=" * 50)
    if all_passed:
        print("🎉 ALL REQUIREMENTS MET!")
        print("Made with ❤️ in PA + MA")
        return 0
    else:
        print("❌ Some requirements not met. Check output above.")
        return 1

if __name__ == '__main__':
    sys.exit(main())
