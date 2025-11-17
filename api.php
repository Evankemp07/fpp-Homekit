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

    $ep = array(
        'method' => 'GET',
        'endpoint' => 'mqtt-diagnostics',
        'callback' => 'fppHomekitMQTTDiagnostics');
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
    
    // Get REAL FPP status via MQTT (not just checking if fppd process exists)
    $fppStatus = array(
        'playing' => false, 
        'status_name' => 'unknown',
        'current_sequence' => '',
        'current_playlist' => '',
        'seconds_elapsed' => 0,
        'seconds_remaining' => 0,
        'volume' => 0,
        'status_text' => 'Unknown',
        'error_detail' => ''
    );
    
    // Get MQTT config
    $mqttBroker = 'localhost';
    $mqttPort = 1883;
    $mqttTopicPrefix = 'FPP';
    
    if (file_exists($configFile)) {
        $config = @json_decode(file_get_contents($configFile), true);
        if ($config) {
            if (isset($config['mqtt']['broker'])) {
                $mqttBroker = $config['mqtt']['broker'];
            }
            if (isset($config['mqtt']['port'])) {
                $mqttPort = intval($config['mqtt']['port']);
            }
        }
    }
    
    // Python script to check FPP status via MQTT
    $pythonStatusScript = <<<'PYCODE'
import sys, json, time
try:
    import paho.mqtt.client as mqtt
except ImportError:
    print(json.dumps({"error": "paho-mqtt not installed", "available": False}))
    sys.exit(0)

broker = sys.argv[1] if len(sys.argv) > 1 else 'localhost'
port = int(sys.argv[2]) if len(sys.argv) > 2 else 1883
prefix = sys.argv[3] if len(sys.argv) > 3 else 'FPP'

status_data = {"available": False, "timeout": True}

def on_connect(client, userdata, flags, rc, *args, **kwargs):
    if rc == 0:
        client.subscribe(f"{prefix}/status")

def on_message(client, userdata, msg):
    global status_data
    try:
        data = json.loads(msg.payload.decode('utf-8'))
        status_data = {
            "available": True,
            "timeout": False,
            "status_name": data.get("status_name", "unknown"),
            "status": data.get("status", 0),
            "current_playlist": data.get("current_playlist", {}).get("playlist", ""),
            "current_sequence": data.get("current_sequence", ""),
            "seconds_played": data.get("seconds_played", 0),
            "seconds_remaining": data.get("seconds_remaining", 0)
        }
    except:
        pass

try:
    try:
        client = mqtt.Client(client_id="fpp-hk-status", callback_api_version=mqtt.CallbackAPIVersion.VERSION2)
    except:
        client = mqtt.Client(client_id="fpp-hk-status")
    
    client.on_connect = on_connect
    client.on_message = on_message
    client.connect(broker, port, keepalive=5)
    client.loop_start()
    
    for _ in range(20):
        if status_data.get("available"):
            break
        time.sleep(0.1)
    
    client.loop_stop()
    client.disconnect()
    print(json.dumps(status_data))
except Exception as e:
    print(json.dumps({"error": str(e), "available": False}))
PYCODE;
    
    $command = "timeout 3 python3 -W ignore -c " . escapeshellarg($pythonStatusScript) . " " . 
               escapeshellarg($mqttBroker) . " " . 
               escapeshellarg($mqttPort) . " " . 
               escapeshellarg($mqttTopicPrefix) . " 2>/dev/null";
    
    $output = shell_exec($command);
    
    if ($output) {
        $mqttStatus = @json_decode(trim($output), true);
        if ($mqttStatus && is_array($mqttStatus)) {
            if (isset($mqttStatus['available']) && $mqttStatus['available']) {
                // Got real status from FPP via MQTT
                $fppStatus['status_name'] = $mqttStatus['status_name'] ?? 'unknown';
                $fppStatus['current_playlist'] = $mqttStatus['current_playlist'] ?? '';
                $fppStatus['current_sequence'] = $mqttStatus['current_sequence'] ?? '';
                $fppStatus['seconds_elapsed'] = $mqttStatus['seconds_played'] ?? 0;
                $fppStatus['seconds_remaining'] = $mqttStatus['seconds_remaining'] ?? 0;
                $fppStatus['playing'] = ($mqttStatus['status'] ?? 0) == 1;
                $fppStatus['status_text'] = 'FPP Available';
                $fppStatus['error_detail'] = '';
            } elseif (isset($mqttStatus['timeout']) && $mqttStatus['timeout']) {
                $fppStatus['status_text'] = 'FPP Not Responding';
                $fppStatus['error_detail'] = 'MQTT connected but FPP not publishing. Is fppd running?';
            } elseif (isset($mqttStatus['error'])) {
                $fppStatus['status_text'] = 'MQTT Error';
                $fppStatus['error_detail'] = $mqttStatus['error'];
            }
        }
    } else {
        $fppStatus['status_text'] = 'Status Check Failed';
        $fppStatus['error_detail'] = 'Could not check FPP status via MQTT.';
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
                // Use numeric signal values (SIGTERM=15, SIGKILL=9) since constants may not be defined
                $sigterm = defined('SIGTERM') ? SIGTERM : 15;
                $sigkill = defined('SIGKILL') ? SIGKILL : 9;
                
                if (posix_kill($pid, 0)) {
                    posix_kill($pid, $sigterm);
                    sleep(1);
                    if (posix_kill($pid, 0)) {
                        posix_kill($pid, $sigkill);
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
    $scriptsDir = $pluginDir . '/scripts';
    $configFile = $scriptsDir . '/homekit_config.json';
    
    $result = array('playlist_name' => '');
    
    // Load plugin config
    if (file_exists($configFile)) {
        $config = @json_decode(file_get_contents($configFile), true);
        if ($config) {
            $result = $config;
        }
    }
    
    // If MQTT settings not in plugin config, read from FPP settings as defaults
    if (!isset($result['mqtt']) || empty($result['mqtt']['broker'])) {
        // Use Python to read MQTT config (same logic as homekit_service.py)
        $pythonGetConfigScript = <<<PYCODE
import sys
import os
import json

plugin_dir = sys.argv[1]
config_file = os.path.join(plugin_dir, 'homekit_config.json')

mqtt_config = {
    'broker': 'localhost',
    'port': 1883,
    'topic_prefix': 'FPP',
    'username': None,
    'password': None
}

# Read plugin config
if os.path.exists(config_file):
    try:
        with open(config_file, 'r') as f:
            plugin_config = json.load(f)
            if 'mqtt' in plugin_config:
                mqtt_config.update(plugin_config['mqtt'])
    except:
        pass

# Read FPP settings files
settings_paths = [
    '/home/fpp/media/settings',
    '/opt/fpp/media/settings'
]

for path in settings_paths:
    if os.path.exists(path):
        try:
            with open(path, 'r') as f:
                for line in f:
                    line = line.strip()
                    if line.startswith('MQTTHost='):
                        mqtt_config['broker'] = line.split('=', 1)[1].strip()
                    elif line.startswith('MQTTPort='):
                        try:
                            mqtt_config['port'] = int(line.split('=', 1)[1].strip())
                        except:
                            pass
                    elif line.startswith('MQTTUsername='):
                        mqtt_config['username'] = line.split('=', 1)[1].strip()
                    elif line.startswith('MQTTPassword='):
                        mqtt_config['password'] = line.split('=', 1)[1].strip()
                    elif line.startswith('MQTTPrefix='):
                        prefix = line.split('=', 1)[1].strip()
                        if prefix:
                            mqtt_config['topic_prefix'] = prefix
        except:
            pass

print(json.dumps(mqtt_config))
PYCODE;
        
        $getConfigCommand = "python3 -c " . escapeshellarg($pythonGetConfigScript) . " " . escapeshellarg($scriptsDir) . " 2>/dev/null";
        $configOutput = shell_exec($getConfigCommand);
        
        if ($configOutput) {
            $jsonStart = strpos($configOutput, '{"broker"');
            if ($jsonStart !== false) {
                $jsonOutput = substr($configOutput, $jsonStart);
                $mqttConfig = @json_decode(trim($jsonOutput), true);
                if ($mqttConfig && is_array($mqttConfig)) {
                    // Only use FPP settings if plugin config doesn't have MQTT settings
                    if (!isset($result['mqtt'])) {
                        $result['mqtt'] = array();
                    }
                    // Fill in defaults from FPP settings
                    if (empty($result['mqtt']['broker']) || $result['mqtt']['broker'] === 'localhost') {
                        if (!empty($mqttConfig['broker'])) {
                            $result['mqtt']['broker'] = $mqttConfig['broker'];
                        }
                    }
                    if (empty($result['mqtt']['port']) || $result['mqtt']['port'] == 1883) {
                        if (!empty($mqttConfig['port'])) {
                            $result['mqtt']['port'] = $mqttConfig['port'];
                        }
                    }
                    if (empty($result['mqtt']['topic_prefix'])) {
                        if (!empty($mqttConfig['topic_prefix'])) {
                            $result['mqtt']['topic_prefix'] = $mqttConfig['topic_prefix'];
                        }
                    }
                }
            }
        }
    }
    
    return json($result);
}

// POST /api/plugin/fpp-Homekit/config
function fppHomekitSaveConfig() {
    $pluginDir = dirname(__FILE__);
    $scriptsDir = $pluginDir . '/scripts';
    $configFile = $scriptsDir . '/homekit_config.json';
    
    // Ensure scripts directory exists
    if (!is_dir($scriptsDir)) {
        if (!@mkdir($scriptsDir, 0755, true)) {
            return json(array('status' => 'error', 'message' => 'Cannot create scripts directory: ' . $scriptsDir));
        }
    }
    
    // Load existing config
    $config = array('playlist_name' => '');
    if (file_exists($configFile)) {
        $existingContent = @file_get_contents($configFile);
        if ($existingContent !== false) {
            $existing = @json_decode($existingContent, true);
            if ($existing && is_array($existing)) {
                $config = $existing;
            }
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
        $broker = trim($_POST['mqtt_broker']);
        if ($broker !== '') {
            $config['mqtt']['broker'] = $broker;
        }
    }
    
    // Update HomeKit IP
    if (isset($_POST['homekit_ip'])) {
        $homekitIp = trim($_POST['homekit_ip']);
        // Empty string means auto-detect
        $config['homekit_ip'] = $homekitIp;
    }
    
    // Write config file
    $jsonData = json_encode($config, JSON_PRETTY_PRINT);
    if ($jsonData === false) {
        return json(array('status' => 'error', 'message' => 'Failed to encode config as JSON: ' . json_last_error_msg()));
    }
    
    $written = @file_put_contents($configFile, $jsonData);
    if ($written === false) {
        $error = error_get_last();
        $errorMsg = 'Failed to save config file';
        if ($error && isset($error['message'])) {
            $errorMsg .= ': ' . $error['message'];
        }
        if (!is_writable($scriptsDir)) {
            $errorMsg .= ' (directory not writable)';
        } elseif (!is_writable($configFile) && file_exists($configFile)) {
            $errorMsg .= ' (file not writable)';
        }
        return json(array('status' => 'error', 'message' => $errorMsg));
    }
    
    $result = array('status' => 'saved', 'config' => $config);
    return json($result);
}

// POST /api/plugin/fpp-Homekit/test-mqtt
function fppHomekitTestMQTT() {
    $pluginDir = dirname(__FILE__);
    $scriptsDir = $pluginDir . '/scripts';
    $configFile = $scriptsDir . '/homekit_config.json';
    
    // Allow testing with values from UI (broker/port from POST), or use saved config
    $testBroker = isset($_POST['mqtt_broker']) ? trim($_POST['mqtt_broker']) : null;
    $testPort = isset($_POST['mqtt_port']) ? intval($_POST['mqtt_port']) : null;
    
    // Use Python to read MQTT config (same logic as homekit_service.py)
    $pythonGetConfigScript = <<<PYCODE
import sys
import os
import json

# Add scripts directory to path
sys.path.insert(0, sys.argv[1])

try:
    # Import the get_mqtt_config function from homekit_service
    from homekit_service import get_mqtt_config
    
    config = get_mqtt_config()
    result = {
        'broker': config.get('broker', 'localhost'),
        'port': config.get('port', 1883),
        'topic_prefix': config.get('topic_prefix', 'FPP'),
        'username': config.get('username'),
        'password': config.get('password')
    }
    print(json.dumps(result))
except Exception as e:
    # Fallback: read config manually
    import json
    
    plugin_dir = sys.argv[1]
    config_file = os.path.join(plugin_dir, 'homekit_config.json')
    
    mqtt_config = {
        'broker': 'localhost',
        'port': 1883,
        'topic_prefix': 'FPP',
        'username': None,
        'password': None
    }
    
    # Read plugin config
    if os.path.exists(config_file):
        try:
            with open(config_file, 'r') as f:
                plugin_config = json.load(f)
                if 'mqtt' in plugin_config:
                    mqtt_config.update(plugin_config['mqtt'])
        except:
            pass
    
    # Read FPP settings files
    settings_paths = [
        '/home/fpp/media/settings',
        '/opt/fpp/media/settings'
    ]
    
    for path in settings_paths:
        if os.path.exists(path):
            try:
                with open(path, 'r') as f:
                    for line in f:
                        line = line.strip()
                        if line.startswith('MQTTHost='):
                            mqtt_config['broker'] = line.split('=', 1)[1].strip()
                        elif line.startswith('MQTTPort='):
                            try:
                                mqtt_config['port'] = int(line.split('=', 1)[1].strip())
                            except:
                                pass
                        elif line.startswith('MQTTUsername='):
                            mqtt_config['username'] = line.split('=', 1)[1].strip()
                        elif line.startswith('MQTTPassword='):
                            mqtt_config['password'] = line.split('=', 1)[1].strip()
                        elif line.startswith('MQTTPrefix='):
                            prefix = line.split('=', 1)[1].strip()
                            if prefix:
                                mqtt_config['topic_prefix'] = prefix
            except:
                pass
    
    print(json.dumps(mqtt_config))
PYCODE;
    
    $getConfigCommand = "python3 -c " . escapeshellarg($pythonGetConfigScript) . " " . escapeshellarg($scriptsDir) . " 2>/dev/null";
    $configOutput = shell_exec($getConfigCommand);
    
    // Parse MQTT config
    $mqttBroker = 'localhost';
    $mqttPort = 1883;
    $mqttTopicPrefix = 'FPP';
    $mqttUsername = null;
    $mqttPassword = null;
    
    if ($configOutput) {
        $jsonStart = strpos($configOutput, '{"broker"');
        if ($jsonStart !== false) {
            $jsonOutput = substr($configOutput, $jsonStart);
            $mqttConfig = @json_decode(trim($jsonOutput), true);
            if ($mqttConfig && is_array($mqttConfig)) {
                if (isset($mqttConfig['broker'])) {
                    $mqttBroker = $mqttConfig['broker'];
                }
                if (isset($mqttConfig['port'])) {
                    $mqttPort = intval($mqttConfig['port']);
                }
                if (isset($mqttConfig['topic_prefix'])) {
                    $mqttTopicPrefix = $mqttConfig['topic_prefix'];
                }
                if (isset($mqttConfig['username'])) {
                    $mqttUsername = $mqttConfig['username'];
                }
                if (isset($mqttConfig['password'])) {
                    $mqttPassword = $mqttConfig['password'];
                }
            }
        }
    }
    
    // Override with test values from UI if provided
    if ($testBroker !== null && $testBroker !== '') {
        $mqttBroker = $testBroker;
    }
    if ($testPort !== null && $testPort > 0 && $testPort <= 65535) {
        $mqttPort = $testPort;
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
username = sys.argv[4] if len(sys.argv) > 4 and sys.argv[4] != 'None' and sys.argv[4] != '' else None
password = sys.argv[5] if len(sys.argv) > 5 and sys.argv[5] != 'None' and sys.argv[5] != '' else None

connected = False
test_result = {"success": False, "error": ""}

# Callbacks that work with both API v1 and v2
def on_connect(client, userdata, flags, rc, *args, **kwargs):
    global connected
    # In API v2, rc is reason_code; in v1, it's rc
    reason_code = rc if isinstance(rc, int) else getattr(rc, 'value', 0)
    if reason_code == 0:
        connected = True
        test_result["success"] = True
        test_result["message"] = f"Connected to MQTT broker at {broker}:{port}"
    else:
        test_result["error"] = f"Connection failed with code {reason_code}"

def on_disconnect(client, userdata, *args, **kwargs):
    # API v1: (client, userdata, rc)
    # API v2: (client, userdata, disconnect_flags, reason_code, properties)
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
    
    # Set username and password if provided
    if username:
        client.username_pw_set(username, password)
    
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
               escapeshellarg($mqttTopicPrefix) . " " .
               escapeshellarg($mqttUsername !== null ? $mqttUsername : 'None') . " " .
               escapeshellarg($mqttPassword !== null ? $mqttPassword : 'None') . " 2>/dev/null";
    
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

// GET /api/plugin/fpp-Homekit/mqtt-diagnostics
function fppHomekitMQTTDiagnostics() {
    $pluginDir = dirname(__FILE__);
    $scriptsDir = $pluginDir . '/scripts';
    
    // Use Python to read MQTT config (same logic as homekit_service.py)
    $pythonDiagnosticsScript = <<<PYCODE
import sys
import os
import json

plugin_dir = sys.argv[1]
config_file = os.path.join(plugin_dir, 'homekit_config.json')

diagnostics = {
    'settings_source': 'unknown',
    'mqtt_config': {
        'broker': 'localhost',
        'port': 1883,
        'topic_prefix': 'FPP',
        'username': None,
        'password': None,
        'enabled': True
    },
    'topics': {
        'start_playlist': '',
        'stop': '',
        'status': '',
        'playlist_status': ''
    },
    'fpp_settings_files': [],
    'plugin_config_file': config_file,
    'plugin_config_exists': os.path.exists(config_file)
}

# Read plugin config
plugin_config = {}
if os.path.exists(config_file):
    try:
        with open(config_file, 'r') as f:
            plugin_config = json.load(f)
            if 'mqtt' in plugin_config:
                diagnostics['mqtt_config'].update(plugin_config['mqtt'])
                diagnostics['settings_source'] = 'plugin_config'
    except Exception as e:
        diagnostics['error'] = f"Error reading plugin config: {str(e)}"

# Read FPP settings files
settings_paths = [
    '/home/fpp/media/settings',
    '/opt/fpp/media/settings'
]

fpp_settings_found = []
for path in settings_paths:
    if os.path.exists(path):
        fpp_settings_found.append(path)
        try:
            with open(path, 'r') as f:
                for line in f:
                    line = line.strip()
                    if line.startswith('MQTTEnabled='):
                        enabled = line.split('=', 1)[1].strip().lower() in ('1', 'true', 'yes')
                        if not diagnostics['settings_source'] or diagnostics['settings_source'] == 'unknown':
                            diagnostics['mqtt_config']['enabled'] = enabled
                            diagnostics['settings_source'] = f'fpp_settings ({path})'
                    elif line.startswith('MQTTHost='):
                        broker = line.split('=', 1)[1].strip()
                        if not diagnostics['settings_source'] or diagnostics['settings_source'] == 'unknown' or diagnostics['mqtt_config']['broker'] == 'localhost':
                            diagnostics['mqtt_config']['broker'] = broker
                            if diagnostics['settings_source'] == 'unknown':
                                diagnostics['settings_source'] = f'fpp_settings ({path})'
                    elif line.startswith('MQTTPort='):
                        try:
                            port = int(line.split('=', 1)[1].strip())
                            if not diagnostics['settings_source'] or diagnostics['settings_source'] == 'unknown' or diagnostics['mqtt_config']['port'] == 1883:
                                diagnostics['mqtt_config']['port'] = port
                                if diagnostics['settings_source'] == 'unknown':
                                    diagnostics['settings_source'] = f'fpp_settings ({path})'
                        except:
                            pass
                    elif line.startswith('MQTTUsername='):
                        username = line.split('=', 1)[1].strip()
                        if not diagnostics['settings_source'] or diagnostics['settings_source'] == 'unknown' or not diagnostics['mqtt_config']['username']:
                            diagnostics['mqtt_config']['username'] = username
                            if diagnostics['settings_source'] == 'unknown':
                                diagnostics['settings_source'] = f'fpp_settings ({path})'
                    elif line.startswith('MQTTPassword='):
                        password = line.split('=', 1)[1].strip()
                        if not diagnostics['settings_source'] or diagnostics['settings_source'] == 'unknown' or not diagnostics['mqtt_config']['password']:
                            diagnostics['mqtt_config']['password'] = password
                            if diagnostics['settings_source'] == 'unknown':
                                diagnostics['settings_source'] = f'fpp_settings ({path})'
                    elif line.startswith('MQTTPrefix='):
                        prefix = line.split('=', 1)[1].strip()
                        if prefix:
                            if not diagnostics['settings_source'] or diagnostics['settings_source'] == 'unknown' or diagnostics['mqtt_config']['topic_prefix'] == 'FPP':
                                diagnostics['mqtt_config']['topic_prefix'] = prefix
                                if diagnostics['settings_source'] == 'unknown':
                                    diagnostics['settings_source'] = f'fpp_settings ({path})'
        except Exception as e:
            pass

diagnostics['fpp_settings_files'] = fpp_settings_found

# Calculate topics that will be used
prefix = diagnostics['mqtt_config']['topic_prefix']
diagnostics['topics']['start_playlist'] = f"{prefix}/command/StartPlaylist/{{playlist_name}}"
diagnostics['topics']['stop'] = f"{prefix}/command/Stop"
diagnostics['topics']['status'] = f"{prefix}/status"
diagnostics['topics']['playlist_status'] = f"{prefix}/playlist/status"

# Get playlist name if configured
if 'playlist_name' in plugin_config:
    diagnostics['configured_playlist'] = plugin_config['playlist_name']
    diagnostics['topics']['start_playlist'] = f"{prefix}/command/StartPlaylist/{plugin_config['playlist_name']}"

print(json.dumps(diagnostics, indent=2))
PYCODE;
    
    $command = "python3 -c " . escapeshellarg($pythonDiagnosticsScript) . " " . escapeshellarg($scriptsDir) . " 2>/dev/null";
    $output = shell_exec($command);
    
    $result = array('error' => 'Failed to read diagnostics');
    
    if ($output) {
        $jsonStart = strpos($output, '{');
        if ($jsonStart !== false) {
            $jsonOutput = substr($output, $jsonStart);
            $decoded = @json_decode(trim($jsonOutput), true);
            if ($decoded && is_array($decoded)) {
                $result = $decoded;
            }
        }
    }

    return json($result);
}

// GET /api/plugin/fpp-Homekit/network-interfaces
function fppHomekitNetworkInterfaces() {
    $interfaces = array();
    $currentIp = null;
    
    // Read current config
    $pluginDir = dirname(__FILE__);
    $scriptsDir = $pluginDir . '/scripts';
    $configFile = $scriptsDir . '/homekit_config.json';
    
    if (file_exists($configFile)) {
        $configData = @file_get_contents($configFile);
        if ($configData) {
            $config = @json_decode($configData, true);
            if ($config && isset($config['homekit_ip'])) {
                $currentIp = $config['homekit_ip'];
            }
        }
    }
    
    // Get network interfaces using 'ip' command (Linux)
    if (command_exists('ip')) {
        $output = shell_exec('ip -o addr show scope global 2>/dev/null | grep -v "inet6" | awk \'{print $2 " " $4}\' | sed \'s|/.*||\'');
        if ($output) {
            $lines = explode("\n", trim($output));
            foreach ($lines as $line) {
                $parts = preg_split('/\s+/', trim($line));
                if (count($parts) >= 2 && $parts[1]) {
                    $interfaces[] = array(
                        'name' => $parts[0],
                        'ip' => $parts[1]
                    );
                }
            }
        }
    }
    
    // Fallback: try ifconfig (macOS/BSD)
    if (empty($interfaces) && command_exists('ifconfig')) {
        $output = shell_exec('ifconfig 2>/dev/null | grep "inet " | grep -v "127.0.0.1" | awk \'{print $2}\'');
        if ($output) {
            $ips = explode("\n", trim($output));
            foreach ($ips as $idx => $ip) {
                if ($ip) {
                    $interfaces[] = array(
                        'name' => 'Interface ' . ($idx + 1),
                        'ip' => $ip
                    );
                }
            }
        }
    }
    
    // Auto-detect current IP if not set
    if (!$currentIp && !empty($interfaces)) {
        $currentIp = $interfaces[0]['ip'];  // Use first interface
    }
    
    $result = array(
        'interfaces' => $interfaces,
        'current_ip' => $currentIp
    );
    
    return json($result);
}

function command_exists($cmd) {
    $return = shell_exec(sprintf("which %s 2>/dev/null", escapeshellarg($cmd)));
    return !empty($return);
}

?>
