#!/bin/bash

# Development Server Startup Script for FPP HomeKit Plugin

echo "Starting FPP HomeKit Plugin Development Server..."
echo ""
echo "Server will be available at: http://localhost:8000"
echo "Press Ctrl+C to stop the server"
echo ""

# Check if PHP is available
if ! command -v php &> /dev/null; then
    echo "Error: PHP is not installed or not in PATH"
    echo "Please install PHP to run the development server"
    exit 1
fi

# Get PHP version
PHP_VERSION=$(php -r 'echo PHP_VERSION;')
echo "Using PHP version: $PHP_VERSION"
echo ""

# Change to plugin directory
cd "$(dirname "$0")"

# Start PHP built-in server
php -S localhost:8000 dev-server.php

