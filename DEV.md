# Development Server

This plugin includes a development server for local testing without requiring a full FPP installation.

## Quick Start

1. **Start the development server:**
   ```bash
   ./dev-start.sh
   ```
   
   Or manually:
   ```bash
   php -S localhost:8000 dev-server.php
   ```

2. **Open your browser:**
   ```
   http://localhost:8000
   ```

3. **Navigate between pages:**
   - Status: `http://localhost:8000?page=status.php`
   - Configuration: `http://localhost:8000?page=content.php`
   - About: `http://localhost:8000?page=about.php`
   - Help: `http://localhost:8000?page=help.php`

## Features

- **Mock API endpoints** - The dev server provides mock responses for API calls
- **Full UI testing** - Test all pages and styling without FPP
- **Hot reload** - Just refresh your browser after making changes
- **Debug mode** - PHP errors are displayed for easier debugging

## Limitations

The development server uses mock data, so:
- Service status will show as "stopped" (no actual Python service)
- QR codes won't be generated (no HAP-python state files)
- FPP API calls will fail gracefully
- Playlist lists will be empty

## Requirements

- PHP 7.0 or higher
- PHP built-in web server (included with PHP)

## Stopping the Server

Press `Ctrl+C` in the terminal where the server is running.

