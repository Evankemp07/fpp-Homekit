<?php
/*
A way for plugins to provide their own PHP API endpoints.

To use, create a file called api.php file in the plugin's directory
and provide a getEndpointsPLUGINNAME() function which returns an
array describing the endpoints the plugin implements.  Since PHP
does not allow hyphens in function names, any hyphens in the plugin
name must be removed when substituting for PLUGINNAME above and if
the plugin name is used in any callback function names.  It is
also best to use unique endpoint names as shown below to eliminate
any conflicts with stock FPP code or other plugin API callbacks.

All endpoints are prefixed with /api/plugin/PLUGIN-NAME but only
the part after PLUGIN-NAME is specified in the getEndpointsPLUGINNAME()
data.  The plugin name is used as-is in the endpoint URL, hyphens
are not removed.  -- limonade.php is used for the underlying implementation so
param("param1" ) can be used for an api like /api/plugin/fpp-BigButtons/:param1

Here is a simple example which would add a
/api/plugin/fpp-BigButtons/version endpoint to the fpp-Bigbuttons plugin.
*/


function getEndpointsfppHomekit() {
    $result = array();

    $ep = array(
        'method' => 'GET',
        'endpoint' => 'status',
        'callback' => 'fppHomekitStatus');
    array_push($result, $ep);

    $ep = array(
        'method' => 'GET',
        'endpoint' => 'qr-code',
        'callback' => 'fppHomekitQRCode');
    array_push($result, $ep);

    $ep = array(
        'method' => 'POST',
        'endpoint' => 'restart',
        'callback' => 'fppHomekitRestart');
    array_push($result, $ep);

    $ep = array(
        'method' => 'GET',
        'endpoint' => 'pairing-info',
        'callback' => 'fppHomekitPairingInfo');
    array_push($result, $ep);

    $ep = array(
        'method' => 'GET',
        'endpoint' => 'playlists',
        'callback' => 'fppHomekitPlaylists');
    array_push($result, $ep);

    $ep = array(
        'method' => 'GET',
        'endpoint' => 'config',
        'callback' => 'fppHomekitGetConfig');
    array_push($result, $ep);

    $ep = array(
        'method' => 'POST',
        'endpoint' => 'config',
        'callback' => 'fppHomekitSaveConfig');
    array_push($result, $ep);

    $ep = array(
        'method' => 'POST',
        'endpoint' => 'test-mqtt',
        'callback' => 'fppHomekitTestMQTT');
    array_push($result, $ep);

    return $result;
}

function fppHomekitSettingsCandidates() {
    $candidates = array();
    $envFppDir = getenv('FPPDIR');
    if (!empty($envFppDir)) {
        $candidates[] = rtrim($envFppDir, '/') . '/settings';
    }
    $envMediaDir = getenv('MEDIADIR');
    if (!empty($envMediaDir)) {
        $candidates[] = rtrim($envMediaDir, '/') . '/settings';
        $candidates[] = rtrim(dirname($envMediaDir), '/') . '/settings';
    }
    $candidates[] = '/home/fpp/media/settings';
    $candidates[] = '/opt/fpp/media/settings';
    
    $unique = array();
    foreach ($candidates as $path) {
        if ($path && !in_array($path, $unique) && file_exists($path)) {
            $unique[] = $path;
        }
    }
    return $unique;
}

function fppHomekitReadHttpPort($settingsPath) {
    if (!$settingsPath || !is_readable($settingsPath)) {
        return 0;
    }
    $contents = @file_get_contents($settingsPath);
    if ($contents && preg_match('/^HTTPPort\s*=\s*(\d+)/m', $contents, $matches)) {
        $port = (int)$matches[1];
        if ($port > 0) {
            return $port;
        }
    }
    return 0;
}

function fppHomekitBuildApiEndpoints() {
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }
    
    $pluginDir = dirname(__FILE__);
    $apiConfigFile = $pluginDir . '/scripts/fpp_api_config.json';
    
    $hosts = array();
    $ports = array();
    
    // First priority: Read from detected API config file (created by install script)
    if (file_exists($apiConfigFile)) {
        $apiConfig = @json_decode(file_get_contents($apiConfigFile), true);
        if ($apiConfig && isset($apiConfig['host']) && isset($apiConfig['port'])) {
            $hosts[] = $apiConfig['host'];
            $ports[] = (int)$apiConfig['port'];
        }
    }
    
    // Second priority: Environment variables
    $envHost = getenv('FPP_API_HOST');
    if (!empty($envHost) && !in_array($envHost, $hosts)) {
        $hosts[] = $envHost;
    }
    
    $envPort = getenv('FPP_API_PORT');
    if (!empty($envPort) && is_numeric($envPort)) {
        $port = (int)$envPort;
        if (!in_array($port, $ports)) {
            $ports[] = $port;
        }
    }
    
    // Third priority: Read from FPP settings files
    foreach (fppHomekitSettingsCandidates() as $path) {
        $port = fppHomekitReadHttpPort($path);
        if ($port > 0 && !in_array($port, $ports)) {
            $ports[] = $port;
        }
    }
    
    // Add default hosts if none found
    if (empty($hosts)) {
        $hosts[] = 'localhost';
        $hosts[] = '127.0.0.1';
    }
    
    // Add common fallback ports if none found
    if (empty($ports)) {
        $ports[] = 32320; // Default FPP port
        $ports[] = 80;
        $ports[] = 8080;
    }
    
    $hosts = array_values(array_unique(array_filter($hosts)));
    $ports = array_values(array_unique(array_filter($ports)));
    
    // Prioritize detected port (first in array)
    $endpoints = array();
    foreach ($hosts as $host) {
        foreach ($ports as $port) {
            if ($port === 80) {
                $endpoints[] = "http://{$host}/api";
            } else {
                $endpoints[] = "http://{$host}:{$port}/api";
            }
        }
    }
    
    if (empty($endpoints)) {
        $endpoints[] = 'http://localhost/api';
    }
    
    $cached = array_values(array_unique($endpoints));
    return $cached;
}

function fppHomekitApiRequest($method, $path, $options = array()) {
    $method = strtoupper($method);
    $timeout = isset($options['timeout']) ? (int)$options['timeout'] : 3;
    $connectTimeout = isset($options['connect_timeout']) ? (int)$options['connect_timeout'] : 2;
    $body = isset($options['body']) ? $options['body'] : null;
    $headers = isset($options['headers']) ? $options['headers'] : array();
    
    $path = '/' . ltrim($path, '/');
    
    $lastError = '';
    $lastHttpCode = 0;
    $lastEndpoint = '';
    $lastResponse = '';
    $lastUrl = '';
    
    foreach (fppHomekitBuildApiEndpoints() as $endpoint) {
        $url = rtrim($endpoint, '/') . $path;
        $lastEndpoint = $endpoint;
        $lastUrl = $url;
        
        $ch = curl_init($url);
        if ($ch === false) {
            $lastError = 'Failed to initialize curl for ' . $url;
            continue;
        }
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $connectTimeout);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        
        if ($method === 'POST' || $method === 'PUT' || $method === 'PATCH') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $errno = curl_errno($ch);
        curl_close($ch);
        
        if ($errno !== 0 || $error) {
            $lastError = $error ?: 'Curl error ' . $errno . ' for ' . $url;
            continue;
        }
        
        $lastResponse = $response;
        $lastHttpCode = $httpCode;
        
        if ($httpCode >= 200 && $httpCode < 300 && $response !== false && $response !== '') {
            return array(
                'success' => true,
                'endpoint' => $endpoint,
                'http_code' => $httpCode,
                'body' => $response
            );
        }
    }
    
    return array(
        'success' => false,
        'endpoint' => $lastEndpoint,
        'url' => $lastUrl,
        'http_code' => $lastHttpCode,
        'body' => $lastResponse,
        'error' => $lastError
    );
}

// GET /api/plugin/fpp-Homekit/status
function fppHomekitStatus() {
    $pluginDir = dirname(__FILE__);
    $pidFile = $pluginDir . '/scripts/homekit_service.pid';
    $configFile = $pluginDir . '/scripts/homekit_config.json';
    $stateFile = $pluginDir . '/scripts/homekit_accessory.state';
    
    $result = array();
    
    // Check if service is running
    $running = false;
    if (file_exists($pidFile)) {
        $pid = trim(file_get_contents($pidFile));
        if ($pid) {
            // Check if process is running (works on Unix-like systems)
            if (function_exists('posix_kill')) {
                if (@posix_kill($pid, 0)) {
                    $running = true;
                }
            }

            if (!$running) {
                // Fallback #1: /proc/<pid>
                if (file_exists("/proc/{$pid}")) {
                    $running = true;
                }
            }

            if (!$running) {
                // Fallback #2: kill -0
                $output = array();
                $return_var = 0;
                @exec("kill -0 $pid 2>/dev/null", $output, $return_var);
                if ($return_var === 0) {
                    $running = true;
                }
            }

            if (!$running) {
                // Fallback #3: ps command (works on macOS/BusyBox)
                $output = array();
                $return_var = 0;
                @exec("ps $pid 2>/dev/null", $output, $return_var);
                if ($return_var === 0 && count($output) > 1) {
                    $running = true;
                }
            }
        }
    }
    $result['service_running'] = $running;
    
    // Check pairing status
    $paired = false;
    if (file_exists($stateFile)) {
        $stateData = @file_get_contents($stateFile);
        if ($stateData) {
            $state = @json_decode($stateData, true);
            if ($state && isset($state['paired_clients']) && count($state['paired_clients']) > 0) {
                $paired = true;
            }
        }
    }
    $result['paired'] = $paired;
    
    // FPP status is monitored via MQTT by the Python service
    // PHP status page just shows basic info
    $fppStatus = array(
        'playing' => false, 
        'status_name' => 'unknown',
        'current_sequence' => '',
        'current_playlist' => '',
        'seconds_elapsed' => 0,
        'seconds_remaining' => 0,
        'volume' => 0,
        'status_text' => 'Status via MQTT',
        'error_detail' => 'FPP status is monitored via MQTT by the HomeKit service. Check service logs for details.'
    );
    
    // Check if FPPD process is running (just for info)
    $fppdRunning = false;
    if (function_exists('exec')) {
        $output = array();
        @exec('pgrep -f fppd 2>/dev/null', $output);
        if (!empty($output)) {
            $fppdRunning = true;
        } else {
            @exec('ps aux | grep -i "[f]ppd" 2>/dev/null', $output);
            if (!empty($output)) {
                $fppdRunning = true;
            }
        }
    }
    
    if ($fppdRunning) {
        $fppStatus['status_text'] = 'FPP Running (MQTT)';
        $fppStatus['error_detail'] = 'FPP daemon is running. Control and status updates use MQTT.';
    } else {
        $fppStatus['status_text'] = 'FPP Not Running';
        $fppStatus['error_detail'] = 'FPP daemon does not appear to be running. Start FPP to enable control.';
    }
    
    $result['fpp_status'] = $fppStatus;
    $result['control_method'] = 'MQTT';
    
    // Get configured playlist
    $playlist = '';
    if (file_exists($configFile)) {
        $config = @json_decode(file_get_contents($configFile), true);
        if ($config && isset($config['playlist_name'])) {
            $playlist = $config['playlist_name'];
        }
    }
    $result['playlist'] = $playlist;
    
    return json($result);
}

// GET /api/plugin/fpp-Homekit/qr-code
function fppHomekitQRCode() {
    $pluginDir = dirname(__FILE__);
    $infoFile = $pluginDir . '/scripts/homekit_pairing_info.json';
    $stateFile = $pluginDir . '/scripts/homekit_accessory.state';
    
    // Try to get setup code and setup ID from pairing info file first
    $setupCode = '123-45-678';
    $setupID = 'HOME';
    
    if (file_exists($infoFile)) {
        $infoData = @file_get_contents($infoFile);
        if ($infoData) {
            $info = @json_decode($infoData, true);
            if ($info) {
                if (isset($info['setup_code'])) {
                    $setupCode = $info['setup_code'];
                }
                if (isset($info['setup_id'])) {
                    $setupID = $info['setup_id'];
                }
            }
        }
    } elseif (file_exists($stateFile)) {
        // Fallback to state file
        $stateData = @file_get_contents($stateFile);
        if ($stateData) {
            $state = @json_decode($stateData, true);
            if ($state) {
                if (isset($state['pincode'])) {
                    $setupCode = $state['pincode'];
                }
                if (isset($state['mac'])) {
                    // Use last 4 chars of MAC as setup ID
                    $setupID = strtoupper(substr($state['mac'], -4));
                }
            }
        }
    }
    
    // Check if QR code data is already in the info file
    $qrData = null;
    if (file_exists($infoFile)) {
        $infoData = @file_get_contents($infoFile);
        if ($infoData) {
            $info = @json_decode($infoData, true);
            if ($info && isset($info['qr_data'])) {
                $qrData = $info['qr_data'];
            }
        }
    }
    
    // If not available, generate QR code data
    if (!$qrData) {
        // Format: X-HM://[8-char hex setup ID][setup code without dashes]
        // Setup code should be 8 digits: XXX-XX-XXX -> XXXXXXXX
        $setupCodeClean = str_replace('-', '', $setupCode);
        if (strlen($setupCodeClean) !== 8) {
            // Invalid setup code format
            header('HTTP/1.1 500 Internal Server Error');
            return json(array('error' => 'Invalid setup code format'));
        }
        
        // Setup ID should already be hex (4 chars), pad to 8 if needed
        $setupIDHex = strtoupper($setupID);
        if (strlen($setupIDHex) < 8) {
            // If setup ID is shorter, pad with zeros or use MAC address
            if (strlen($setupIDHex) === 4) {
                // Common case: 4-char setup ID, duplicate it
                $setupIDHex = $setupIDHex . $setupIDHex;
            } else {
                $setupIDHex = str_pad($setupIDHex, 8, '0', STR_PAD_LEFT);
            }
        }
        $setupIDHex = substr($setupIDHex, 0, 8);
        
        $qrData = "X-HM://" . $setupIDHex . $setupCodeClean;
    }
    
    // Use Python to generate QR code image
    $pythonScript = <<<'PYCODE'
import sys
import io
import base64

try:
    import qrcode
except Exception as exc:
    sys.stderr.write(f"QR_IMPORT_ERROR:{exc}")
    sys.exit(1)

if len(sys.argv) < 2:
    sys.stderr.write("QR_NO_DATA")
    sys.exit(1)

qr_data = sys.argv[1]

qr = qrcode.QRCode(version=1, box_size=10, border=4)
qr.add_data(qr_data)
qr.make(fit=True)
img = qr.make_image(fill_color="black", back_color="white")
buf = io.BytesIO()
img.save(buf, format="PNG")
sys.stdout.write(base64.b64encode(buf.getvalue()).decode("ascii"))
PYCODE;

    $command = "python3 -c " . escapeshellarg($pythonScript) . " " . escapeshellarg($qrData);
    $output = shell_exec($command);
    
    if ($output) {
        $decoded = base64_decode(trim($output), true);
        if ($decoded !== false) {
            header('Content-Type: image/png');
            echo $decoded;
            return;
        }
    }
    
    // Fallback: return QR code data as JSON
    $result = array(
        'qr_data' => $qrData,
        'setup_code' => $setupCode,
        'setup_id' => $setupID
    );
    return json($result);
}

// POST /api/plugin/fpp-Homekit/restart
function fppHomekitRestart() {
    $pluginDir = dirname(__FILE__);
    $pidFile = $pluginDir . '/scripts/homekit_service.pid';
    $script = $pluginDir . '/scripts/homekit_service.py';
    $startScript = $pluginDir . '/scripts/postStart.sh';
    
    // Stop service
    if (file_exists($pidFile)) {
        $pid = trim(file_get_contents($pidFile));
        if ($pid) {
            // Try posix_kill if available
            if (function_exists('posix_kill')) {
                if (posix_kill($pid, 0)) {
                    posix_kill($pid, SIGTERM);
                    sleep(1);
                    if (posix_kill($pid, 0)) {
                        posix_kill($pid, SIGKILL);
                    }
                }
            } else {
                // Fallback: use kill command
                @exec("kill $pid 2>&1", $output, $return_var);
                sleep(1);
                @exec("kill -9 $pid 2>&1", $output, $return_var);
            }
        }
        @unlink($pidFile);
    }
    
    // Start service using postStart.sh script for consistency
    if (file_exists($startScript)) {
        // Use nohup and background execution to ensure it runs
        $cmd = "cd " . escapeshellarg($pluginDir . '/scripts') . " && nohup bash " . escapeshellarg($startScript) . " >> " . escapeshellarg($pluginDir . '/scripts/restart.log') . " 2>&1 &";
        shell_exec($cmd);
        
        // Wait a moment for service to start
        sleep(3);
        
        // Check if service started successfully
        $started = false;
        if (file_exists($pidFile)) {
            $newPid = trim(file_get_contents($pidFile));
            if ($newPid) {
                // Check if process is running
                if (function_exists('posix_kill')) {
                    if (posix_kill($newPid, 0)) {
                        $started = true;
                    }
                } else {
                    $output = array();
                    $return_var = 0;
                    @exec("ps -p $newPid 2>&1", $output, $return_var);
                    if ($return_var === 0 && !empty($output)) {
                        $started = true;
                    }
                }
            }
        }
        
        $result = array(
            'status' => $started ? 'restarted' : 'restart_initiated',
            'started' => $started,
            'message' => $started ? 'Service restarted successfully' : 'Restart initiated, checking status...'
        );
    } elseif (file_exists($script)) {
        // Fallback: start directly
        $python3 = trim(shell_exec("which python3 2>/dev/null"));
        if (empty($python3)) {
            $python3 = 'python3';
        }
        $cmd = "cd " . escapeshellarg($pluginDir . '/scripts') . " && nohup " . escapeshellarg($python3) . " " . escapeshellarg($script) . " >> " . escapeshellarg($pluginDir . '/scripts/homekit_service.log') . " 2>&1 &";
        shell_exec($cmd);
        sleep(3);
        $result = array('status' => 'restarted');
    } else {
        $result = array('status' => 'error', 'message' => 'Service script not found');
    }
    
    return json($result);
}

// GET /api/plugin/fpp-Homekit/pairing-info
function fppHomekitPairingInfo() {
    $pluginDir = dirname(__FILE__);
    $infoFile = $pluginDir . '/scripts/homekit_pairing_info.json';
    $stateFile = $pluginDir . '/scripts/homekit_accessory.state';
    
    $result = array(
        'paired' => false,
        'setup_code' => '123-45-678',
        'setup_id' => 'HOME'
    );
    
    // Get setup code and ID from pairing info file
    if (file_exists($infoFile)) {
        $infoData = @file_get_contents($infoFile);
        if ($infoData) {
            $info = @json_decode($infoData, true);
            if ($info) {
                if (isset($info['setup_code'])) {
                    $result['setup_code'] = $info['setup_code'];
                }
                if (isset($info['setup_id'])) {
                    $result['setup_id'] = $info['setup_id'];
                }
            }
        }
    }
    
    // Check pairing status from state file
    if (file_exists($stateFile)) {
        $stateData = @file_get_contents($stateFile);
        if ($stateData) {
            $state = @json_decode($stateData, true);
            if ($state) {
                if (isset($state['paired_clients']) && count($state['paired_clients']) > 0) {
                    $result['paired'] = true;
                }
            }
        }
    }
    
    return json($result);
}

// GET /api/plugin/fpp-Homekit/playlists
function fppHomekitPlaylists() {
    $playlists = array();
    
    // Read playlists directly from filesystem (MQTT-only, no HTTP API)
    // FPP playlists are stored in /home/fpp/media/playlists as JSON files
    $playlistDirs = array(
        '/home/fpp/media/playlists',  // Standard FPP location
        '/opt/fpp/media/playlists'     // Alternative location
    );
    
    // Add environment variable path if set
    $fppMediaDir = getenv('FPP_MEDIA_DIR');
    if ($fppMediaDir) {
        $playlistDirs[] = $fppMediaDir . '/playlists';
    }
    
    foreach ($playlistDirs as $playlistDir) {
        if ($playlistDir && is_dir($playlistDir) && is_readable($playlistDir)) {
            $files = scandir($playlistDir);
            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..' && pathinfo($file, PATHINFO_EXTENSION) === 'json') {
                    $playlistName = pathinfo($file, PATHINFO_FILENAME);
                    if ($playlistName && !in_array($playlistName, $playlists)) {
                        $playlists[] = $playlistName;
                    }
                }
            }
            break; // Found a valid directory, no need to check others
        }
    }
    
    // Sort playlists alphabetically
    sort($playlists);
    
    $result = array('playlists' => $playlists);
    return json($result);
}

// GET /api/plugin/fpp-Homekit/config
function fppHomekitGetConfig() {
    $pluginDir = dirname(__FILE__);
    $configFile = $pluginDir . '/scripts/homekit_config.json';
    
    $result = array('playlist_name' => '');
    
    if (file_exists($configFile)) {
        $config = @json_decode(file_get_contents($configFile), true);
        if ($config) {
            $result = $config;
        }
    }
    
    return json($result);
}

// POST /api/plugin/fpp-Homekit/config
function fppHomekitSaveConfig() {
    $pluginDir = dirname(__FILE__);
    $configFile = $pluginDir . '/scripts/homekit_config.json';
    
    // Load existing config
    $config = array('playlist_name' => '');
    if (file_exists($configFile)) {
        $existing = @json_decode(file_get_contents($configFile), true);
        if ($existing && is_array($existing)) {
            $config = $existing;
        }
    }
    
    // Update playlist name
    if (isset($_POST['playlist_name'])) {
        $config['playlist_name'] = $_POST['playlist_name'];
    } elseif (isset($_GET['playlist_name'])) {
        $config['playlist_name'] = $_GET['playlist_name'];
    }
    
    // Update MQTT settings
    if (isset($_POST['mqtt_port'])) {
        if (!isset($config['mqtt'])) {
            $config['mqtt'] = array();
        }
        $port = intval($_POST['mqtt_port']);
        if ($port > 0 && $port <= 65535) {
            $config['mqtt']['port'] = $port;
        }
    }
    
    if (isset($_POST['mqtt_broker'])) {
        if (!isset($config['mqtt'])) {
            $config['mqtt'] = array();
        }
        $config['mqtt']['broker'] = trim($_POST['mqtt_broker']);
    }
    
    if (file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT)) !== false) {
        $result = array('status' => 'saved', 'config' => $config);
    } else {
        $result = array('status' => 'error', 'message' => 'Failed to save config');
    }
    
    return json($result);
}

// POST /api/plugin/fpp-Homekit/test-mqtt
function fppHomekitTestMQTT() {
    $pluginDir = dirname(__FILE__);
    $configFile = $pluginDir . '/scripts/homekit_config.json';
    
    // Load config
    $config = array('playlist_name' => '');
    if (file_exists($configFile)) {
        $existing = @json_decode(file_get_contents($configFile), true);
        if ($existing && is_array($existing)) {
            $config = $existing;
        }
    }
    
    // Get MQTT settings
    $mqttBroker = 'localhost';
    $mqttPort = 1883;
    $mqttTopicPrefix = 'FPP';
    
    if (isset($config['mqtt'])) {
        if (isset($config['mqtt']['broker'])) {
            $mqttBroker = $config['mqtt']['broker'];
        }
        if (isset($config['mqtt']['port'])) {
            $mqttPort = intval($config['mqtt']['port']);
        }
        if (isset($config['mqtt']['topic_prefix'])) {
            $mqttTopicPrefix = $config['mqtt']['topic_prefix'];
        }
    }
    
    // Also check FPP settings files for MQTT config
    $settingsPaths = array(
        '/home/fpp/media/settings',
        '/opt/fpp/media/settings'
    );
    
    foreach ($settingsPaths as $path) {
        if (file_exists($path) && is_readable($path)) {
            $content = @file_get_contents($path);
            if ($content) {
                if (preg_match('/^MQTTHost\s*=\s*(.+)$/m', $content, $matches)) {
                    $mqttBroker = trim($matches[1]);
                }
                if (preg_match('/^MQTTPort\s*=\s*(\d+)$/m', $content, $matches)) {
                    $mqttPort = intval($matches[1]);
                }
                if (preg_match('/^MQTTPrefix\s*=\s*(.+)$/m', $content, $matches)) {
                    $mqttTopicPrefix = trim($matches[1]);
                }
            }
        }
    }
    
    // Test MQTT connection using Python
    $pythonScript = <<<PYCODE
import sys
import time
import json
import warnings

# Suppress deprecation warnings
warnings.filterwarnings('ignore', category=DeprecationWarning)

try:
    import paho.mqtt.client as mqtt
except ImportError:
    print(json.dumps({"success": False, "error": "paho-mqtt not installed"}))
    sys.exit(1)

broker = sys.argv[1]
port = int(sys.argv[2])
topic_prefix = sys.argv[3]

connected = False
test_result = {"success": False, "error": ""}

def on_connect(client, userdata, flags, rc):
    global connected
    if rc == 0:
        connected = True
        test_result["success"] = True
        test_result["message"] = f"Connected to MQTT broker at {broker}:{port}"
    else:
        test_result["error"] = f"Connection failed with code {rc}"

def on_disconnect(client, userdata, rc):
    pass

try:
    # Use callback API version 2 if available to avoid deprecation warning
    try:
        client = mqtt.Client(client_id="fpp-homekit-test", callback_api_version=mqtt.CallbackAPIVersion.VERSION2)
    except (AttributeError, TypeError):
        # Fallback for older paho-mqtt versions
        client = mqtt.Client(client_id="fpp-homekit-test")
    client.on_connect = on_connect
    client.on_disconnect = on_disconnect
    
    client.connect(broker, port, keepalive=5)
    client.loop_start()
    
    # Wait for connection (max 3 seconds)
    for _ in range(30):
        if connected:
            break
        time.sleep(0.1)
    
    if connected:
        # Try to publish a test message to see if FPP responds
        test_topic = f"{topic_prefix}/command/GetStatus"
        try:
            result = client.publish(test_topic, "", qos=1)
            if result.rc == mqtt.MQTT_ERR_SUCCESS:
                test_result["message"] += f" - Test message published to {test_topic}"
            else:
                test_result["message"] += f" - Warning: Could not publish test message (rc={result.rc})"
        except Exception as e:
            test_result["message"] += f" - Warning: {str(e)}"
    
    client.loop_stop()
    client.disconnect()
    
    print(json.dumps(test_result))
    
except ConnectionRefusedError:
    print(json.dumps({"success": False, "error": f"Connection refused to {broker}:{port}. Check if MQTT broker is running."}))
except Exception as e:
    print(json.dumps({"success": False, "error": str(e)}))
PYCODE;

    $command = "python3 -W ignore::DeprecationWarning -c " . escapeshellarg($pythonScript) . " " . 
               escapeshellarg($mqttBroker) . " " . 
               escapeshellarg($mqttPort) . " " . 
               escapeshellarg($mqttTopicPrefix) . " 2>/dev/null";
    
    $output = shell_exec($command);
    $result = array('success' => false, 'error' => 'Unknown error');
    
    if ($output) {
        // Try to extract JSON from output (in case there are any warnings before it)
        $jsonStart = strpos($output, '{"success"');
        if ($jsonStart !== false) {
            $jsonOutput = substr($output, $jsonStart);
            $decoded = @json_decode(trim($jsonOutput), true);
            if ($decoded && is_array($decoded)) {
                $result = $decoded;
            } else {
                $result['error'] = 'Failed to parse test result: ' . htmlspecialchars(substr($jsonOutput, 0, 200));
            }
        } else {
            $decoded = @json_decode(trim($output), true);
            if ($decoded && is_array($decoded)) {
                $result = $decoded;
            } else {
                $result['error'] = 'Failed to parse test result: ' . htmlspecialchars(substr($output, 0, 200));
            }
        }
    } else {
        $result['error'] = 'No output from MQTT test script';
    }

    return json($result);
}

?>
