#!/bin/sh

# Stop the HomeKit service when FPP is stopping

PLUGIN_DIR="${MEDIADIR}/plugins/fpp-Homekit"
PID_FILE="${PLUGIN_DIR}/scripts/homekit_service.pid"
LOG_FILE="${PLUGIN_DIR}/scripts/homekit_service.log"

if [ -f "${PID_FILE}" ]; then
    PID=$(cat "${PID_FILE}" 2>/dev/null)
    if [ -n "${PID}" ]; then
        if command -v kill >/dev/null 2>&1; then
            kill "${PID}" 2>/dev/null
            sleep 1
            kill -9 "${PID}" 2>/dev/null
        fi
        echo "$(date '+%Y-%m-%d %H:%M:%S') - Stopped HomeKit service (PID ${PID})" >> "${LOG_FILE}" 2>/dev/null
    fi
    rm -f "${PID_FILE}"
fi

