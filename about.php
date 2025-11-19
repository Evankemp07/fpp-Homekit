<?php
$pluginDir = dirname(__FILE__);
$cssPath = $pluginDir . '/styles.css';
?>

<style>
<?php
if (file_exists($cssPath)) {
    readfile($cssPath);
}
?>
</style>

<div class="homekit-container">
    <div class="homekit-card">
        <h1 style="font-size: 32px; font-weight: 600; margin: 0 0 16px 0; padding-bottom: 8px; border-bottom: 1px solid var(--border-color);">FPP HomeKit Integration Plugin</h1>
        
        <div style="line-height: 1.8; color: var(--text-primary); font-size: 16px;">
            <p style="margin: 0 0 24px 0;">Integrates Falcon Pixel Player with Apple HomeKit, allowing you to control FPP playlists via HomeKit. Built with <a href="https://github.com/ikalchev/HAP-python" target="_blank" style="color: #007aff; text-decoration: none;">HAP-python</a> for reliable, standards-compliant HomeKit support. Transform your FPP installation into a HomeKit-compatible smart light that you can control from Apple's Home app.</p>
            
            <h2 style="font-size: 24px; font-weight: 600; margin: 24px 0 16px 0; padding-top: 24px; border-top: 1px solid var(--border-color);">Features</h2>
            <ul style="margin: 0 0 24px 0; padding-left: 30px;">
                <li style="margin-bottom: 8px;"><strong>Native HomeKit Integration</strong>: Built on HAP-python for reliable HomeKit Accessory Protocol support</li>
                <li style="margin-bottom: 8px;"><strong>Instant Control</strong>: Turn light ON to start playlist, OFF to stop playback (responds in under 100ms)</li>
                <li style="margin-bottom: 8px;"><strong>QR Code Pairing</strong>: Easy setup with scannable QR codes displayed in the interface</li>
                <li style="margin-bottom: 8px;"><strong>Real-time Status</strong>: Monitor HomeKit service status, FPP connection, and pairing state</li>
                <li style="margin-bottom: 8px;"><strong>Command History</strong>: View the last command received through HomeKit (real or emulated)</li>
                <li style="margin-bottom: 8px;"><strong>Playlist Configuration</strong>: Select which playlist starts when HomeKit turns the light ON</li>
                <li style="margin-bottom: 8px;"><strong>Auto-turn-off</strong>: Light automatically turns off when playlists finish playing</li>
                <li style="margin-bottom: 8px;"><strong>Modern UI</strong>: Beautiful Apple-style interface with dark mode support</li>
            </ul>
            
            <h2 style="font-size: 24px; font-weight: 600; margin: 24px 0 16px 0; padding-top: 24px; border-top: 1px solid var(--border-color);">How It Works</h2>
            <p style="margin: 0 0 16px 0;">This plugin uses <a href="https://github.com/ikalchev/HAP-python" target="_blank" style="color: #007aff; text-decoration: none;">HAP-python</a> to implement the HomeKit Accessory Protocol (HAP). The service runs as a background Python process that exposes FPP as a controllable light accessory in Apple's Home app. When you turn the light ON/OFF in Home app, commands are processed instantly and communicated to FPP via MQTT.</p>
            
            <h2 style="font-size: 24px; font-weight: 600; margin: 24px 0 16px 0; padding-top: 24px; border-top: 1px solid var(--border-color);">Installation</h2>
            <ol style="margin: 0 0 24px 0; padding-left: 30px;">
                <li style="margin-bottom: 8px;">Install the plugin through FPP's plugin manager</li>
                <li style="margin-bottom: 8px;">Open the Status page and choose which playlist should start when HomeKit turns the accessory ON</li>
                <li style="margin-bottom: 8px;">Scan the QR code on the Status page with the Home app on your iOS device</li>
                <li style="margin-bottom: 8px;">Control the accessory from HomeKit (turn ON to start the playlist, OFF to stop)</li>
            </ol>
            
            <h2 style="font-size: 24px; font-weight: 600; margin: 24px 0 16px 0; padding-top: 24px; border-top: 1px solid var(--border-color);">Requirements</h2>
            <ul style="margin: 0 0 24px 0; padding-left: 30px;">
                <li style="margin-bottom: 8px;">FPP 9.0 or later</li>
                <li style="margin-bottom: 8px;">Python 3.6+ (Python 3.11+ recommended)</li>
                <li style="margin-bottom: 8px;"><a href="https://github.com/ikalchev/HAP-python" target="_blank" style="color: #007aff; text-decoration: none;">HAP-python</a> library (installed automatically via pip)</li>
                <li style="margin-bottom: 8px;">MQTT broker (configured in FPP)</li>
                <li style="margin-bottom: 8px;">avahi-daemon (for mDNS/Bonjour support)</li>
                <li style="margin-bottom: 8px;">iOS device with Home app (iOS 10 or later)</li>
            </ul>
            
            <h2 style="font-size: 24px; font-weight: 600; margin: 24px 0 16px 0; padding-top: 24px; border-top: 1px solid var(--border-color);">Usage</h2>
            <ol style="margin: 0 0 24px 0; padding-left: 30px;">
                <li style="margin-bottom: 8px;"><strong>Configure Playlist</strong>: On the Status page, select which playlist should start when HomeKit turns the light ON</li>
                <li style="margin-bottom: 8px;"><strong>Pair with HomeKit</strong>: Scan the QR code with your iPhone/iPad using the Home app</li>
                <li style="margin-bottom: 8px;"><strong>Control FPP</strong>: Once paired, control FPP from the Home app - turn the light ON to start your playlist, OFF to stop</li>
            </ol>
            
            <h2 style="font-size: 24px; font-weight: 600; margin: 24px 0 16px 0; padding-top: 24px; border-top: 1px solid var(--border-color);">Troubleshooting</h2>
            <ul style="margin: 0; padding-left: 30px;">
                <li style="margin-bottom: 8px;"><strong>Service not running</strong>: Check that Python dependencies are installed and avahi-daemon is running</li>
                <li style="margin-bottom: 8px;"><strong>Can't pair</strong>: Ensure service is running and your iOS device is on the same network</li>
                <li style="margin-bottom: 8px;"><strong>Playlist doesn't start</strong>: Verify the playlist name is correctly configured and exists in FPP</li>
            </ul>
            
            <h2 style="font-size: 24px; font-weight: 600; margin: 24px 0 16px 0; padding-top: 24px; border-top: 1px solid var(--border-color);">Credits</h2>
            <p style="margin: 0;">Built with <a href="https://github.com/ikalchev/HAP-python" target="_blank" style="color: #007aff; text-decoration: none;">HAP-python</a> by <a href="https://github.com/ikalchev" target="_blank" style="color: #007aff; text-decoration: none;">ikalchev</a> - a Python implementation of the HomeKit Accessory Protocol.</p>
        </div>
    </div>
</div>
