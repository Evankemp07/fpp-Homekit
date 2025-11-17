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
        <h2>About FPP Homekit</h2>
        <p class="info-text"><strong>FPP Homekit</strong> bridges Falcon Player (FPP) with Apple HomeKit so you can launch or stop playlists directly from the Home app or Siri. The plugin handles pairing, status monitoring, and playlist control with an Apple-style UI that supports dark mode.</p>
    </div>

    <div class="homekit-card">
        <h3>Key Features</h3>
        <ul class="info-list">
            <li>Expose FPP as a HomeKit Light accessory—turn ON to start your playlist, OFF to stop</li>
            <li>Display a HomeKit-compatible QR code and setup code for effortless pairing</li>
            <li>Live status panel with service health, pairing state, and detailed FPP playback info</li>
            <li>Inline playlist selection with one-click save—no separate configuration page required</li>
            <li>Automatic dependency installation on first install and auto-start logic for the HomeKit service</li>
        </ul>
    </div>

    <div class="homekit-card">
        <h3>Requirements</h3>
        <ul class="info-list">
            <li>FPP 9.0 or later</li>
            <li>Python 3.6+ with HAP-python (installed during the first plugin install)</li>
            <li>`avahi-daemon` (Bonjour/mDNS) running on the FPP controller</li>
            <li>iOS device with the Home app (iOS 10+)</li>
        </ul>
    </div>

    <div class="homekit-card">
        <h3>Usage</h3>
        <ol class="info-list">
            <li>Install the plugin via FPP’s Plugin Manager. Dependencies are installed automatically the first time.</li>
            <li>Open the <strong>Status</strong> page, choose your default playlist, and let the plugin auto-start the HomeKit service.</li>
            <li>Scan the displayed QR code or enter the setup code in the Home app to add the accessory.</li>
            <li>Use the Home app or Siri to turn the light on/off, which starts or stops your selected playlist.</li>
        </ol>
    </div>

    <div class="homekit-card">
        <h3>Project Links</h3>
        <p class="info-text">
            <a href='https://github.com/Evankemp07/fpp-Homekit' class="link" target="_blank">GitHub Repository</a><br>
            <a href='https://github.com/Evankemp07/fpp-Homekit/issues' class="link" target="_blank">Issue Tracker</a>
        </p>
    </div>
</div>
