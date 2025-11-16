<?php
$pluginDir = dirname(dirname(__FILE__));
$plugin = basename($pluginDir);
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
        <h2>Help</h2>
        
        <h3>Getting Started</h3>
        <ol class="info-list">
            <li><strong>Configure Playlist:</strong> Go to the <a href="plugin.php?plugin=<?php echo $plugin; ?>&page=content.php" class="link">Configuration page</a> and select which playlist should start when HomeKit turns the light ON.</li>
            <li><strong>Start Service:</strong> The HomeKit service should start automatically when FPP starts. Check the <a href="plugin.php?plugin=<?php echo $plugin; ?>&page=status.php" class="link">Status page</a> to verify it's running.</li>
            <li><strong>Pair with HomeKit:</strong> On the Status page, scan the QR code with your iPhone or iPad using the Home app.</li>
            <li><strong>Control FPP:</strong> Once paired, you can control FPP from the Home app - turn the light ON to start your playlist, OFF to stop.</li>
        </ol>
    </div>
    
    <div class="homekit-card">
        <h3>How It Works</h3>
        <p class="info-text">The plugin exposes FPP as a HomeKit Light accessory:</p>
        <ul class="info-list">
            <li><strong>Light ON:</strong> Starts the configured FPP playlist</li>
            <li><strong>Light OFF:</strong> Stops FPP playback</li>
        </ul>
        <p class="info-text">The plugin continuously monitors FPP status and syncs it with HomeKit, so the light state in the Home app reflects the actual playback state.</p>
    </div>
    
    <div class="homekit-card">
        <h3>Troubleshooting</h3>
        <p class="info-text"><strong>Service not running:</strong></p>
        <ul class="info-list">
            <li>Check that Python 3 and required packages are installed</li>
            <li>Verify that avahi-daemon is running (required for mDNS/Bonjour)</li>
            <li>Check FPP logs for error messages</li>
            <li>Try restarting the service from the Status page</li>
        </ul>
        
        <p class="info-text" style="margin-top: 20px;"><strong>Can't pair with HomeKit:</strong></p>
        <ul class="info-list">
            <li>Make sure the service is running</li>
            <li>Ensure your iOS device and FPP are on the same network</li>
            <li>Try entering the setup code manually instead of scanning the QR code</li>
            <li>Check that port 51826 is not blocked by firewall</li>
        </ul>
        
        <p class="info-text" style="margin-top: 20px;"><strong>Playlist doesn't start:</strong></p>
        <ul class="info-list">
            <li>Verify the playlist name is correctly configured</li>
            <li>Check that the playlist exists in FPP</li>
            <li>Ensure FPP API is accessible (port 32320)</li>
        </ul>
    </div>
    
    <div class="homekit-card">
        <h3>Requirements</h3>
        <ul class="info-list">
            <li>Python 3.6 or newer</li>
            <li>HAP-python library (installed automatically)</li>
            <li>avahi-daemon (for mDNS/Bonjour support)</li>
            <li>FPP API access (default port 32320)</li>
            <li>iOS device with Home app (iOS 10 or later)</li>
        </ul>
    </div>
</div>
