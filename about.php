<style>
:root {
    --bg-primary: #ffffff;
    --bg-secondary: #f5f5f7;
    --text-primary: #1d1d1f;
    --text-secondary: #86868b;
    --border-color: #d2d2d7;
    --card-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

@media (prefers-color-scheme: dark) {
    :root {
        --bg-primary: #1d1d1f;
        --bg-secondary: #2c2c2e;
        --text-primary: #f5f5f7;
        --text-secondary: #86868b;
        --border-color: #38383a;
        --card-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
    }
}

.homekit-container {
    max-width: 680px;
    margin: 0 auto;
    padding: 20px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
    color: var(--text-primary);
    background: var(--bg-secondary);
    min-height: 100vh;
}

.homekit-card {
    background: var(--bg-primary);
    border-radius: 18px;
    padding: 24px;
    margin-bottom: 16px;
    box-shadow: var(--card-shadow);
    border: 1px solid var(--border-color);
}

.homekit-card h2 {
    margin: 0 0 20px 0;
    font-size: 28px;
    font-weight: 600;
    letter-spacing: -0.5px;
    color: var(--text-primary);
}

.info-text {
    color: var(--text-secondary);
    font-size: 15px;
    line-height: 1.6;
    margin: 12px 0;
}

.info-text strong {
    color: var(--text-primary);
}

.feature-list {
    list-style: none;
    padding: 0;
    margin: 16px 0;
}

.feature-list li {
    padding: 8px 0;
    color: var(--text-secondary);
    font-size: 15px;
    line-height: 1.6;
}

.feature-list li:before {
    content: "• ";
    color: #007aff;
    font-weight: bold;
    margin-right: 8px;
}

.link {
    color: #007aff;
    text-decoration: none;
    font-size: 15px;
}

.link:hover {
    text-decoration: underline;
}
</style>

<div class="homekit-container">
    <div class="homekit-card">
        <h2>About</h2>
        <p class="info-text"><strong>FPP HomeKit Integration Plugin</strong></p>
        <p class="info-text">This plugin integrates Falcon Pixel Player with Apple HomeKit, allowing you to control FPP playlists via the Home app on iOS devices.</p>
        
        <ul class="feature-list">
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
