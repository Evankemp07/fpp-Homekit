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
        <h2>About</h2>
        <p class="info-text"><strong>FPP HomeKit Integration Plugin</strong></p>
        <p class="info-text">This plugin integrates Falcon Pixel Player with Apple HomeKit, allowing you to control FPP playlists via the Home app on iOS devices.</p>
        
        <ul class="info-list">
            <li>Control FPP playlists via HomeKit</li>
            <li>QR code pairing for easy setup</li>
            <li>Status monitoring and configuration</li>
        </ul>
        
        <p class="info-text" style="margin-top: 24px;">
            <a href='https://github.com/FalconChristmas/fpp-Homekit' class="link" target="_blank">Git Repository</a><br>
            <a href='https://github.com/FalconChristmas/fpp-Homekit/issues' class="link" target="_blank">Bug Reporter</a>
        </p>
    </div>
</div>
