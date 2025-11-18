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
            <p style="margin: 0 0 24px 0;">Integrates Falcon Pixel Player with Apple HomeKit, allowing you to control FPP playlists via HomeKit. Provides QR code pairing and status monitoring.</p>
            
            <h2 style="font-size: 24px; font-weight: 600; margin: 24px 0 16px 0; padding-top: 24px; border-top: 1px solid var(--border-color);">Features</h2>
            <ul style="margin: 0 0 24px 0; padding-left: 30px;">
                <li style="margin-bottom: 8px;">Control FPP playlists via HomeKit (turn light ON to start playlist, OFF to stop)</li>
                <li style="margin-bottom: 8px;">QR code pairing for easy setup</li>
                <li style="margin-bottom: 8px;">Real-time status monitoring</li>
                <li style="margin-bottom: 8px;">Configuration page to select which playlist to start</li>
                <li style="margin-bottom: 8px;">Apple-style UI with dark mode support</li>
            </ul>
            
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
                <li style="margin-bottom: 8px;">Python 3.6+</li>
                <li style="margin-bottom: 8px;">HAP-python library (installed automatically)</li>
                <li style="margin-bottom: 8px;">avahi-daemon (for mDNS/Bonjour support)</li>
                <li style="margin-bottom: 8px;">iOS device with Home app (iOS 10 or later)</li>
            </ul>
            
            <h2 style="font-size: 24px; font-weight: 600; margin: 24px 0 16px 0; padding-top: 24px; border-top: 1px solid var(--border-color);">Usage</h2>
            <ol style="margin: 0 0 24px 0; padding-left: 30px;">
                <li style="margin-bottom: 8px;"><strong>Configure Playlist</strong>: On the Status page, select which playlist should start when HomeKit turns the light ON</li>
                <li style="margin-bottom: 8px;"><strong>Pair with HomeKit</strong>: On the Status page, scan the QR code with your iPhone/iPad using the Home app</li>
                <li style="margin-bottom: 8px;"><strong>Control FPP</strong>: Once paired, control FPP from the Home app - turn the light ON to start your playlist, OFF to stop</li>
            </ol>
            
            <h2 style="font-size: 24px; font-weight: 600; margin: 24px 0 16px 0; padding-top: 24px; border-top: 1px solid var(--border-color);">Troubleshooting</h2>
            <ul style="margin: 0; padding-left: 30px;">
                <li style="margin-bottom: 8px;"><strong>Service not running</strong>: Check that Python dependencies are installed and avahi-daemon is running</li>
                <li style="margin-bottom: 8px;"><strong>Can't pair</strong>: Ensure service is running and your iOS device is on the same network</li>
                <li style="margin-bottom: 8px;"><strong>Playlist doesn't start</strong>: Verify the playlist name is correctly configured and exists in FPP</li>
            </ul>
        </div>
    </div>
</div>
