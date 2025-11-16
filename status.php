<?php
// Get plugin directory
$pluginDir = dirname(__FILE__);
$plugin = basename($pluginDir);

// Load CSS file
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
        <h2>HomeKit Status</h2>
        
        <div id="message-container"></div>
        
        <div id="status-content">
            <div class="status-row">
                <span class="status-label">HomeKit Service</span>
                <span class="status-value" id="service-status">
                    <span class="status-indicator stopped"></span>Loading...
                </span>
            </div>
            
            <div class="status-row">
                <span class="status-label">Pairing Status</span>
                <span class="status-value" id="pairing-status">
                    <span class="status-indicator not-paired"></span>Loading...
                </span>
            </div>
            
            <div class="status-row">
                <span class="status-label">FPP Status</span>
                <span class="status-value" id="fpp-status">
                    <span class="status-indicator stopped"></span>Loading...
                </span>
            </div>
            
            <div class="status-row">
                <span class="status-label">Configured Playlist</span>
                <span class="status-value" id="playlist-status">
                    Loading...
                </span>
            </div>
            
            <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--border-color);">
                <button class="homekit-button" onclick="restartService()" id="restart-btn">Restart Service</button>
                <button class="homekit-button secondary" onclick="refreshStatus()" id="refresh-btn">Refresh</button>
                <button class="homekit-button secondary" onclick="viewLogs()" id="view-logs-btn" style="display: none;">View Logs</button>
            </div>
            
            <div id="service-log-section" style="display: none; margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--border-color);">
                <h4 style="margin: 0 0 12px 0; font-size: 16px; font-weight: 600;">Service Log</h4>
                <pre id="service-log-content" style="background: var(--bg-secondary); padding: 12px; border-radius: 8px; font-family: 'SF Mono', Monaco, monospace; font-size: 12px; overflow-x: auto; max-height: 300px; overflow-y: auto; color: var(--text-secondary); margin: 0;">Loading log...</pre>
            </div>
        </div>
        
        <div style="margin-top: 32px; padding-top: 32px; border-top: 1px solid var(--border-color);">
            <h3>Pair with HomeKit</h3>
            <div id="pairing-section">
                <p class="info-text">To pair this accessory with HomeKit:</p>
                <ol class="info-list">
                    <li>Open the Home app on your iPhone or iPad</li>
                    <li>Tap the "+" button to add an accessory</li>
                    <li>Scan the QR code below, or enter the setup code manually</li>
                </ol>
                
                <div class="qr-container">
                    <div id="qr-loading" style="display: none;">
                        <p class="info-text">Generating QR code...</p>
                    </div>
                    <div id="qr-content" style="display: none;">
                        <div class="qr-code">
                            <img id="qr-image" alt="HomeKit QR Code" style="max-width: 280px; display: block;" />
                        </div>
                        <div class="setup-code">
                            <span id="setup-code-text"></span>
                            <button class="copy-btn" onclick="copySetupCode()" title="Copy Setup Code" id="copy-btn">
                                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M5.5 2.5H11.5C12.0523 2.5 12.5 2.94772 12.5 3.5V9.5C12.5 10.0523 12.0523 10.5 11.5 10.5H9.5V12.5C9.5 13.0523 9.05228 13.5 8.5 13.5H2.5C1.94772 13.5 1.5 13.0523 1.5 12.5V6.5C1.5 5.94772 1.94772 5.5 2.5 5.5H4.5V3.5C4.5 2.94772 4.94772 2.5 5.5 2.5Z" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M4.5 5.5H8.5C9.05228 5.5 9.5 5.94772 9.5 6.5V10.5" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div id="qr-error" style="display: none;">
                        <p style="color: var(--error-color);">Service is not running. Please start the service to generate QR code.</p>
                    </div>
                </div>
            </div>
            
            <div id="paired-section" style="display: none;">
                <p class="success-message">✓ Successfully paired with HomeKit</p>
                <p class="info-text">You can now control FPP from the Home app on your iOS devices.</p>
            </div>
        </div>
        
        <div style="margin-top: 32px; padding-top: 32px; border-top: 1px solid var(--border-color);">
            <h3>Information</h3>
            <p class="info-text"><strong>Accessory Name:</strong> FPP-Controller</p>
            <p class="info-text"><strong>Accessory Type:</strong> Light</p>
            <p class="info-text"><strong>Control:</strong> Turning the light ON will start the configured playlist. Turning it OFF will stop playback.</p>
        </div>
        
        <div style="margin-top: 32px; padding-top: 32px; border-top: 1px solid var(--border-color);">
            <h4 class="debug-header" onclick="toggleDebug()" style="margin: 0 0 16px 0; cursor: pointer; user-select: none; display: flex; align-items: center; gap: 8px; color: var(--text-primary); font-weight: 600;">
                <span class="debug-toggle-icon" id="debug-toggle-icon" style="display: inline-block; transition: transform 0.2s; transform: rotate(-90deg);">▼</span>
                Debug Messages
            </h4>
            <div class="debug-messages" id="debug-messages" style="display: none; color: var(--text-secondary); line-height: 1.6; max-height: 300px; overflow-y: auto; font-family: 'SF Mono', Monaco, monospace; font-size: 12px;">
                <div>No debug messages yet...</div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    const API_BASE = '/api/plugin/<?php echo $plugin; ?>';
    let refreshInterval = null;
    
    // Debug logging
    function debugLog(message, data = null) {
        const debugContainer = document.getElementById('debug-messages');
        if (!debugContainer) return;
        
        const placeholder = debugContainer.querySelector('div:only-child');
        if (placeholder && placeholder.textContent === 'No debug messages yet...') {
            placeholder.remove();
        }
        
        const timestamp = new Date().toLocaleTimeString();
        const logEntry = document.createElement('div');
        logEntry.className = 'debug-entry';
        
        let content = '<strong style="color: var(--info-color);">[' + timestamp + ']</strong> ' + escapeHtml(message);
        if (data) {
            content += '<pre>' + escapeHtml(JSON.stringify(data, null, 2)) + '</pre>';
        }
        logEntry.innerHTML = content;
        
        debugContainer.insertBefore(logEntry, debugContainer.firstChild);
        
        // Keep only last 15 messages
        while (debugContainer.children.length > 15) {
            debugContainer.removeChild(debugContainer.lastChild);
        }
    }
    
    // Toggle debug section
    window.toggleDebug = function() {
        const debugMessages = document.getElementById('debug-messages');
        const toggleIcon = document.getElementById('debug-toggle-icon');
        if (!debugMessages || !toggleIcon) return;
        
        const isHidden = debugMessages.style.display === 'none' || 
                        (debugMessages.style.display === '' && !debugMessages.classList.contains('open'));
        
        if (isHidden) {
            debugMessages.style.display = 'block';
            debugMessages.classList.add('open');
            toggleIcon.style.transform = 'rotate(0deg)';
            toggleIcon.classList.add('open');
        } else {
            debugMessages.style.display = 'none';
            debugMessages.classList.remove('open');
            toggleIcon.style.transform = 'rotate(-90deg)';
            toggleIcon.classList.remove('open');
        }
    };
    
    // Show message
    function showMessage(message, type) {
        const container = document.getElementById('message-container');
        const msgDiv = document.createElement('div');
        msgDiv.className = 'message ' + type;
        msgDiv.innerHTML = escapeHtml(message);
        msgDiv.style.opacity = '0';
        msgDiv.style.transform = 'translateY(-20px)';
        container.innerHTML = '';
        container.appendChild(msgDiv);
        
        setTimeout(() => {
            msgDiv.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            msgDiv.style.opacity = '1';
            msgDiv.style.transform = 'translateY(0)';
        }, 10);
        
        if (type === 'success') {
            setTimeout(() => {
                msgDiv.style.opacity = '0';
                msgDiv.style.transform = 'translateY(-20px)';
                setTimeout(() => msgDiv.remove(), 500);
            }, 5000);
        }
    }
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Copy setup code to clipboard
    window.copySetupCode = function() {
        const codeText = document.getElementById('setup-code-text').textContent;
        if (!codeText) return;
        
        navigator.clipboard.writeText(codeText).then(() => {
            const btn = document.getElementById('copy-btn');
            const originalHTML = btn.innerHTML;
            btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M3 8L6 11L13 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';
            setTimeout(() => {
                btn.innerHTML = originalHTML;
            }, 2000);
        }).catch(err => {
            debugLog('Failed to copy setup code', { error: err.message });
        });
    };
    
    // Load status
    function loadStatus() {
        debugLog('Loading status...');
        fetch(API_BASE + '/status')
            .then(response => {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                debugLog('Status response', data);
                updateStatusDisplay(data);
            })
            .catch(error => {
                debugLog('Error loading status', { error: error.message });
                showMessage('Error loading status: ' + error.message, 'error');
            });
    }
    
    // Update status display
    function updateStatusDisplay(data) {
        const serviceRunning = data.service_running || false;
        const paired = data.paired || false;
        const fppStatus = data.fpp_status || {};
        const playlist = data.playlist || '';
        
        // Update service status
        const serviceStatusEl = document.getElementById('service-status');
        serviceStatusEl.innerHTML = '<span class="status-indicator ' + (serviceRunning ? 'running' : 'stopped') + '"></span>' + 
            (serviceRunning ? 'Running' : 'Stopped');
        
        // Update pairing status
        const pairingStatusEl = document.getElementById('pairing-status');
        pairingStatusEl.innerHTML = '<span class="status-indicator ' + (paired ? 'paired' : 'not-paired') + '"></span>' + 
            (paired ? 'Paired' : 'Not Paired');
        
        // Update FPP status
        const playing = fppStatus.playing || fppStatus.status_name === 'playing';
        const statusName = fppStatus.status_name || 'unknown';
        const fppStatusEl = document.getElementById('fpp-status');
        fppStatusEl.innerHTML = '<span class="status-indicator ' + (playing ? 'playing' : 'stopped') + '"></span>' + 
            (playing ? 'Playing' : capitalize(statusName));
        
        // Update playlist
        const playlistEl = document.getElementById('playlist-status');
        if (playlist) {
            playlistEl.innerHTML = escapeHtml(playlist);
        } else {
            playlistEl.innerHTML = '<span style="color: var(--warning-color);">Not configured - <a href="plugin.php?plugin=<?php echo $plugin; ?>&page=content.php" class="link">Configure now</a></span>';
        }
        
        // Show/hide view logs button based on service status
        const viewLogsBtn = document.getElementById('view-logs-btn');
        if (viewLogsBtn) {
            viewLogsBtn.style.display = serviceRunning ? 'none' : 'inline-block';
        }
        
        // Show/hide pairing sections
        const pairingSection = document.getElementById('pairing-section');
        const pairedSection = document.getElementById('paired-section');
        
        if (paired) {
            pairingSection.style.display = 'none';
            pairedSection.style.display = 'block';
        } else {
            pairingSection.style.display = 'block';
            pairedSection.style.display = 'none';
            
            if (serviceRunning) {
                loadQRCode();
            } else {
                document.getElementById('qr-loading').style.display = 'none';
                document.getElementById('qr-content').style.display = 'none';
                document.getElementById('qr-error').style.display = 'block';
            }
        }
    }
    
    // Load QR code
    function loadQRCode() {
        document.getElementById('qr-loading').style.display = 'block';
        document.getElementById('qr-content').style.display = 'none';
        document.getElementById('qr-error').style.display = 'none';
        
        fetch(API_BASE + '/pairing-info')
            .then(response => {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                debugLog('Pairing info response', data);
                const setupCode = data.setup_code || '123-45-678';
                document.getElementById('setup-code-text').textContent = setupCode;
                
                // Load QR code image
                const qrImage = document.getElementById('qr-image');
                qrImage.src = API_BASE + '/qr-code?' + new Date().getTime();
                qrImage.onload = () => {
                    document.getElementById('qr-loading').style.display = 'none';
                    document.getElementById('qr-content').style.display = 'block';
                };
                qrImage.onerror = () => {
                    document.getElementById('qr-loading').style.display = 'none';
                    document.getElementById('qr-error').style.display = 'block';
                };
            })
            .catch(error => {
                debugLog('Error loading pairing info', { error: error.message });
                document.getElementById('qr-loading').style.display = 'none';
                document.getElementById('qr-error').style.display = 'block';
            });
    }
    
    // Restart service
    window.restartService = function() {
        if (!confirm('Are you sure you want to restart the HomeKit service?')) {
            return;
        }
        
        const btn = document.getElementById('restart-btn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner"></span> Restarting...';
        
        debugLog('Restarting service...');
        fetch(API_BASE + '/restart', {
            method: 'POST'
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            debugLog('Restart response', data);
            showMessage('Service restart initiated. Please wait a few seconds...', 'info');
            setTimeout(() => {
                loadStatus();
                btn.disabled = false;
                btn.textContent = 'Restart Service';
            }, 3000);
        })
        .catch(error => {
            debugLog('Error restarting service', { error: error.message });
            showMessage('Error restarting service: ' + error.message, 'error');
            btn.disabled = false;
            btn.textContent = 'Restart Service';
        });
    };
    
    // Refresh status
    window.refreshStatus = function() {
        const btn = document.getElementById('refresh-btn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner"></span> Refreshing...';
        loadStatus();
        setTimeout(() => {
            btn.disabled = false;
            btn.textContent = 'Refresh';
        }, 1000);
    };
    
    function capitalize(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }
    
    // View service logs
    window.viewLogs = function() {
        const logSection = document.getElementById('service-log-section');
        const logContent = document.getElementById('service-log-content');
        const viewLogsBtn = document.getElementById('view-logs-btn');
        
        if (logSection.style.display === 'none') {
            logSection.style.display = 'block';
            viewLogsBtn.textContent = 'Hide Logs';
            logContent.textContent = 'Loading log...';
            
            fetch(API_BASE + '/log')
                .then(response => response.json())
                .then(data => {
                    if (data.log_exists && data.log_content) {
                        logContent.textContent = data.log_content || '(Log file is empty)';
                    } else {
                        logContent.textContent = '(No log file found. Service may not have started yet.)';
                    }
                })
                .catch(error => {
                    logContent.textContent = 'Error loading log: ' + error.message;
                });
        } else {
            logSection.style.display = 'none';
            viewLogsBtn.textContent = 'View Logs';
        }
    };
    
    // Initialize
    document.addEventListener('DOMContentLoaded', function() {
        loadStatus();
        
        // Auto-refresh every 30 seconds
        refreshInterval = setInterval(loadStatus, 30000);
    });
})();
</script>
