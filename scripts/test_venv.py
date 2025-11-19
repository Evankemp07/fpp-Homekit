#!/usr/bin/env python3
"""
Test script to verify venv setup and dependencies
"""

import sys
import os

def test_venv():
    """Test if running in venv and check dependencies"""
    print("=== FPP HomeKit Venv Test ===")

    # Check if in venv
    in_venv = hasattr(sys, 'real_prefix') or (hasattr(sys, 'base_prefix') and sys.base_prefix != sys.prefix)
    print(f"Running in virtual environment: {in_venv}")

    if in_venv:
        print(f"Python executable: {sys.executable}")
        print(f"Prefix: {sys.prefix}")

    # Test dependencies
    deps = ['pyhap', 'paho.mqtt', 'requests']
    failed_deps = []

    for dep in deps:
        try:
            __import__(dep.replace('.', '_') if '.' in dep else dep)
            print(f"✓ {dep} - OK")
        except ImportError as e:
            print(f"✗ {dep} - FAILED: {e}")
            failed_deps.append(dep)

    if failed_deps:
        print(f"\n❌ Missing dependencies: {', '.join(failed_deps)}")
        print("Run: ./install_venv.sh")
        return False
    else:
        print("\n✅ All dependencies available!")

    # Test HomeKit imports
    try:
        from pyhap.accessory import Accessory
        from pyhap.accessory_driver import AccessoryDriver
        print("✅ HomeKit (HAP-python) imports successful!")
    except ImportError as e:
        print(f"❌ HomeKit imports failed: {e}")
        return False

    print("\n🎉 Venv setup is working correctly!")
    return True

if __name__ == '__main__':
    success = test_venv()
    sys.exit(0 if success else 1)
