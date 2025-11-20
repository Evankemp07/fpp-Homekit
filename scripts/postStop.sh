#!/bin/sh

# Stop the HomeKit service when FPP is stopping
# This script ensures all instances of homekit_service.py are terminated

PLUGIN_DIR="${MEDIADIR}/plugins/fpp-Homekit"
PID_FILE="${PLUGIN_DIR}/scripts/homekit_service.pid"
LOG_FILE="${PLUGIN_DIR}/scripts/homekit_service.log"

# First, try to stop using PID file
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

# Fallback: kill all instances by process name to catch any orphaned processes
# This handles cases where the PID file is stale or missing
if command -v pkill >/dev/null 2>&1; then
    pkill -f homekit_service.py 2>/dev/null && echo "$(date '+%Y-%m-%d %H:%M:%S') - Killed remaining homekit_service.py processes via pkill" >> "${LOG_FILE}" 2>/dev/null || true
elif command -v killall >/dev/null 2>&1; then
    killall homekit_service.py 2>/dev/null && echo "$(date '+%Y-%m-%d %H:%M:%S') - Killed remaining homekit_service.py processes via killall" >> "${LOG_FILE}" 2>/dev/null || true
else
    # Fallback: loop through ps output
    ps aux | grep '[h]omekit_service.py' | awk '{print $2}' | while read -r pid; do
        if [ -n "$pid" ]; then
            kill "$pid" 2>/dev/null || true
            sleep 1
            kill -9 "$pid" 2>/dev/null || true
            echo "$(date '+%Y-%m-%d %H:%M:%S') - Killed orphaned homekit_service.py process (PID ${pid})" >> "${LOG_FILE}" 2>/dev/null
        fi
    done
fi

