<?php

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

    $ep = array(
        'method' => 'GET',
        'endpoint' => 'network-interfaces',
        'callback' => 'fppHomekitNetworkInterfaces');
    array_push($result, $ep);

    $ep = array(
        'method' => 'GET',
        'endpoint' => 'diagnostics',
        'callback' => 'fppHomekitDiagnostics');
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
    if ($contents) {
        // Try multiple patterns to find HTTPPort
        // Pattern 1: HTTPPort=32320 or HTTPPort = 32320 (multiline match)
        if (preg_match('/^HTTPPort\s*[=:]\s*(\d+)/m', $contents, $matches)) {
            $port = (int)$matches[1];
            if ($port > 0) {
                return $port;
            }
        }
        // Pattern 2: Look for any HTTPPort line (case-insensitive, more flexible)
        $lines = explode("\n", $contents);
        foreach ($lines as $line) {
            $line = trim($line);
            if (preg_match('/^HTTPPort\s*[=:]\s*(\d+)/i', $line, $matches)) {
                $port = (int)$matches[1];
                if ($port > 0) {
                    return $port;
                }
            }
        }
    }
    return 0;
}

function fppHomekitDetectHttpPortsFromSystem() {
    $ports = array();
    if (!function_exists('shell_exec')) {
        return $ports;
    }
    
    $commands = array(
        'ss -tlnp | grep fppd',
        'netstat -tulpn | grep fppd'
    );
    
    foreach ($commands as $cmd) {
        $output = @shell_exec($cmd . ' 2>/dev/null');
        if (!$output) {
            continue;
        }
        
        $lines = explode("\n", trim($output));
        foreach ($lines as $line) {
            if (!$line) {
                continue;
            }
            
            if (preg_match_all('/:(\d+)\s+/', $line, $matches)) {
                foreach ($matches[1] as $portStr) {
                    $port = (int)$portStr;
                    if ($port > 0 && !in_array($port, $ports)) {
                        $ports[] = $port;
                    }
                }
            } elseif (preg_match('/\[(\d+)\]/', $line, $matches)) {
                // IPv6 format like [::]:32320
                $port = (int)$matches[1];
                if ($port > 0 && !in_array($port, $ports)) {
                    $ports[] = $port;
                }
            }
        }
        
        if (!empty($ports)) {
            break; // Ports found; no need to run other commands
        }
    }
    
    return $ports;
}

function fppHomekitDetectHostIPs() {
    $hosts = array();
    
    $prependIfValid = function(&$list, $value) {
        if (!empty($value) && is_string($value)) {
            $value = trim($value);
            // Skip if empty, IPv6, localhost variants, or external IPs
            if ($value === '' || strpos($value, ':') !== false || $value === '::1' || $value === '127.0.0.1' || $value === 'localhost') {
                return;
            }
            // Only accept valid IPv4 addresses (exclude IPv6 and invalid formats)
            if (filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
                return;
            }
            // Additional check: only accept private IPs (192.168.x.x, 10.x.x.x, 172.16-31.x.x)
            if (!preg_match('/^(192\.168\.|10\.|172\.(1[6-9]|2[0-9]|3[0-1])\.)/', $value)) {
                return;
            }
            if (!in_array($value, $list)) {
                $list[] = $value;
            }
        }
    };
    
    // Server environment
    if (isset($_SERVER['SERVER_ADDR'])) {
        $prependIfValid($hosts, $_SERVER['SERVER_ADDR']);
    }
    if (isset($_SERVER['SERVER_NAME'])) {
        $resolved = @gethostbyname($_SERVER['SERVER_NAME']);
        if ($resolved && $resolved !== $_SERVER['SERVER_NAME']) {
            $prependIfValid($hosts, $resolved);
        }
    }
    
    // Hostname based detection
    $hostname = @gethostname();
    if ($hostname) {
        $resolved = @gethostbyname($hostname);
        if ($resolved && $resolved !== $hostname) {
            $prependIfValid($hosts, $resolved);
        }
    }
    
    $unameHost = php_uname('n');
    if ($unameHost && $unameHost !== $hostname) {
        $resolved = @gethostbyname($unameHost);
        if ($resolved && $resolved !== $unameHost) {
            $prependIfValid($hosts, $resolved);
        }
    }
    
    // hostname -I
    if (function_exists('shell_exec')) {
        $output = @shell_exec('hostname -I 2>/dev/null');
        if (!empty($output)) {
            $tokens = preg_split('/\s+/', trim($output));
            foreach ($tokens as $token) {
                $prependIfValid($hosts, $token);
            }
        }
        
        // ip -4 -o addr show
        $ipOutput = @shell_exec('ip -4 -o addr show 2>/dev/null');
        if (!empty($ipOutput)) {
            $lines = explode("\n", trim($ipOutput));
            foreach ($lines as $line) {
                if (preg_match('/inet\s+(\d+\.\d+\.\d+\.\d+)/', $line, $matches)) {
                    $prependIfValid($hosts, $matches[1]);
                }
            }
        }
        
        // ifconfig fallback (macOS/BSD)
        $ifconfigOutput = @shell_exec('ifconfig 2>/dev/null');
        if (!empty($ifconfigOutput)) {
            if (preg_match_all('/inet\s+(\d+\.\d+\.\d+\.\d+)/', $ifconfigOutput, $matches)) {
                foreach ($matches[1] as $ip) {
                    $prependIfValid($hosts, $ip);
                }
            }
        }
    }
    
    return $hosts;
}

function fppHomekitBuildApiEndpoints() {
    // Disable caching temporarily to ensure fresh endpoint order after updates
    // This ensures 32320 is always tried first
    // TODO: Re-enable caching once endpoint order is stable
    // static $cached = null;
    // if ($cached !== null) {
    //     return $cached;
    // }
    
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
            // Debug: log that config was loaded
            error_log("FPP API Config loaded: {$apiConfig['host']}:{$apiConfig['port']}");
        } else {
            error_log("FPP API Config file exists but invalid: " . json_encode($apiConfig));
        }
    } else {
        error_log("FPP API Config file not found at: $apiConfigFile");
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

    // Fourth priority: Detect from system sockets (ss/netstat)
    foreach (fppHomekitDetectHttpPortsFromSystem() as $detectedPort) {
        if ($detectedPort > 0 && !in_array($detectedPort, $ports)) {
            $ports[] = $detectedPort;
        }
    }

    // Enrich hosts list with detected IPs from the system
    foreach (fppHomekitDetectHostIPs() as $detectedHost) {
        if (!in_array($detectedHost, $hosts)) {
            $hosts[] = $detectedHost;
        }
    }
    
    // Add default hosts if none found
    if (empty($hosts)) {
        $hosts[] = 'localhost';
        $hosts[] = '127.0.0.1';
    }
    
    // ALWAYS ensure 32320 is in the ports list (FPP default)
    // Even if detected ports exist, 32320 should be tried first
    if (!in_array(32320, $ports)) {
        array_unshift($ports, 32320);
    }
    
    // Add common fallback ports if none found or if we want to scan
    // Common FPP ports to try
    $commonPorts = array(32320, 32321, 32322, 80, 8080, 8000, 8888);
    foreach ($commonPorts as $commonPort) {
        if (!in_array($commonPort, $ports)) {
            $ports[] = $commonPort;
        }
    }
    
    // If still empty (shouldn't happen), add defaults
    if (empty($ports)) {
        $ports[] = 32320;
    }
    
    $hosts = array_values(array_unique(array_filter($hosts)));
    $ports = array_values(array_unique(array_filter($ports)));
    
    // Prioritize detected port (first in array)
    // Also prioritize localhost/127.0.0.1 first, and port 32320 first
    $endpoints = array();
    
    // Sort hosts: localhost first, then 127.0.0.1, then others
    $sortedHosts = array();
    $hasLocalhost = false;
    $has127 = false;
    
    // First, collect localhost and 127.0.0.1
    foreach ($hosts as $host) {
        if ($host === 'localhost') {
            $hasLocalhost = true;
        } elseif ($host === '127.0.0.1') {
            $has127 = true;
        } else {
            $sortedHosts[] = $host;
        }
    }
    
    // Add localhost first, then 127.0.0.1
    if ($hasLocalhost) {
        array_unshift($sortedHosts, 'localhost');
    }
    if ($has127) {
        // Insert 127.0.0.1 after localhost
        if ($hasLocalhost) {
            array_splice($sortedHosts, 1, 0, '127.0.0.1');
        } else {
            array_unshift($sortedHosts, '127.0.0.1');
        }
    }
    
    if (empty($sortedHosts)) {
        $sortedHosts = array('localhost', '127.0.0.1');
    }
    
    // Sort ports: ALWAYS put 32320 first (FPP default), then others
    $sortedPorts = array();
    // ALWAYS start with 32320 (FPP default port)
    $sortedPorts[] = 32320;
    // Then add other ports (excluding 32320 if it was already in the list)
    foreach ($ports as $port) {
        if ($port != 32320) {
            $sortedPorts[] = $port;
        }
    }
    // Remove duplicates while preserving order
    $sortedPorts = array_values(array_unique($sortedPorts));
    
    // Build endpoints: try localhost:32320 first, then other combinations
    foreach ($sortedHosts as $host) {
        foreach ($sortedPorts as $port) {
            if ($port === 80) {
                $endpoints[] = "http://{$host}/api";
            } else {
                $endpoints[] = "http://{$host}:{$port}/api";
            }
        }
    }
    
    if (empty($endpoints)) {
        $endpoints[] = 'http://localhost:32320/api';
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
    $triedEndpoints = array(); // Track all endpoints tried for debugging
    
    $endpoints = fppHomekitBuildApiEndpoints();
    foreach ($endpoints as $endpoint) {
        $url = rtrim($endpoint, '/') . $path;
        $lastEndpoint = $endpoint;
        $lastUrl = $url;
        $triedEndpoints[] = $url; // Track this endpoint
        
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
        'error' => $lastError,
        'tried_endpoints' => $triedEndpoints, // Show all endpoints that were tried
        'first_endpoint' => !empty($triedEndpoints) ? $triedEndpoints[0] : null
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
    
    // Get REAL FPP status - try HTTP API first (more reliable), fallback to MQTT
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
    
    // Use MQTT to get FPP status (more reliable than HTTP API)
    $apiData = null;
    $lastError = '';

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
            if (isset($config['mqtt']['topic_prefix'])) {
                $mqttTopicPrefix = $config['mqtt']['topic_prefix'];
            }
        }
    }

    // Python script to check FPP status via MQTT
    $pythonStatusScript = <<<'PYCODE'
import sys, json, time
try:
    import paho.mqtt.client as mqtt
except ImportError:
    error_msg = "paho-mqtt not installed. Install with: python3 -m pip install paho-mqtt --user"
    print(json.dumps({"error": error_msg, "available": False, "install_command": "python3 -m pip install paho-mqtt --user"}))
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
            "current_playlist": data.get("current_playlist", {}).get("playlist", "") if isinstance(data.get("current_playlist"), dict) else data.get("current_playlist", ""),
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

    for _ in range(30):  # Wait up to 3 seconds
        if status_data.get("available"):
            break
        time.sleep(0.1)

    client.loop_stop()
    client.disconnect()
    print(json.dumps(status_data))
except Exception as e:
    print(json.dumps({"error": str(e), "available": False}))
PYCODE;

    // Try with timeout
    $hasTimeout = @shell_exec('which timeout 2>/dev/null');

    if ($hasTimeout) {
        $command = "timeout 5 python3 -W ignore -c " . escapeshellarg($pythonStatusScript) . " " .
                   escapeshellarg($mqttBroker) . " " .
                   escapeshellarg($mqttPort) . " " .
                   escapeshellarg($mqttTopicPrefix) . " 2>&1";
    } else {
        // No timeout command, run directly (with Python timeout in script)
        $command = "python3 -W ignore -c " . escapeshellarg($pythonStatusScript) . " " .
                   escapeshellarg($mqttBroker) . " " .
                   escapeshellarg($mqttPort) . " " .
                   escapeshellarg($mqttTopicPrefix) . " 2>&1";
    }

    $output = @shell_exec($command);

    if ($output) {
        $mqttStatus = @json_decode(trim($output), true);
        if ($mqttStatus && is_array($mqttStatus)) {
            if (isset($mqttStatus['available']) && $mqttStatus['available']) {
                // Got real status from FPP via MQTT
                $apiData = array(
                    'status_name' => $mqttStatus['status_name'] ?? 'unknown',
                    'status' => $mqttStatus['status'] ?? 0,
                    'current_playlist' => $mqttStatus['current_playlist'] ?? '',
                    'current_sequence' => $mqttStatus['current_sequence'] ?? '',
                    'seconds_played' => $mqttStatus['seconds_played'] ?? 0,
                    'seconds_remaining' => $mqttStatus['seconds_remaining'] ?? 0,
                    'volume' => 0, // MQTT doesn't provide volume
                    'playing' => ($mqttStatus['status'] ?? 0) === 1
                );
            } elseif (isset($mqttStatus['timeout']) && $mqttStatus['timeout']) {
                $lastError = 'FPP MQTT status timeout - FPP may not be publishing status updates';
            } elseif (isset($mqttStatus['error'])) {
                $errorMsg = $mqttStatus['error'];
                if (strpos($errorMsg, 'Connection refused') !== false) {
                    $lastError = 'Cannot connect to MQTT broker at ' . $mqttBroker . ':' . $mqttPort . '. Start mosquitto: sudo systemctl start mosquitto';
                } elseif (strpos($errorMsg, 'paho-mqtt not installed') !== false || strpos($errorMsg, 'paho.mqtt') !== false) {
                    // Show helpful install instructions
                    $installCmd = isset($mqttStatus['install_command']) ? $mqttStatus['install_command'] : 'python3 -m pip install paho-mqtt --user';
                    $lastError = 'paho-mqtt Python package is not installed. Install it with: ' . $installCmd;
                } else {
                    $lastError = 'MQTT Error: ' . $errorMsg;
                }
            }
        } else {
            // Failed to parse JSON - might be a Python error
            $lastError = 'MQTT status check failed: ' . htmlspecialchars(substr($output, 0, 200));
        }
    } else {
        // No output - command might have failed
        $lastError = 'MQTT status check command failed - check if python3 is available';
    }

    // Fallback to HTTP API if MQTT fails
    if (!$apiData && empty($lastError)) {
        // Build more helpful error message
        $lastError = $apiResult['error'] ?: "Failed to connect to FPP API";
        
        // Check if FPP daemon is running and what ports it's listening on
        $fppRunning = false;
        $listeningPorts = array();
        if (function_exists('shell_exec')) {
            // Check if fppd process exists
            $fppCheck = @shell_exec('pgrep -f "fppd" 2>/dev/null');
            if (!empty($fppCheck)) {
                $fppRunning = true;
            } else {
                // Alternative check
                $fppCheck2 = @shell_exec('ps aux | grep -i "[f]ppd" 2>/dev/null');
                if (!empty($fppCheck2)) {
                    $fppRunning = true;
                }
            }
            
            // If FPP is running, try to detect what ports it's actually listening on
            if ($fppRunning) {
                // Try netstat first (common on most systems)
                $netstatOutput = @shell_exec('netstat -tuln 2>/dev/null | grep LISTEN | grep -E ":(80|443|8080|32320|32321)" 2>/dev/null');
                if (!$netstatOutput) {
                    // Try ss command (newer Linux systems)
                    $netstatOutput = @shell_exec('ss -tuln 2>/dev/null | grep LISTEN | grep -E ":(80|443|8080|32320|32321)" 2>/dev/null');
                }
                if ($netstatOutput) {
                    // Parse output to find ports
                    if (preg_match_all('/:(\d+)\s/', $netstatOutput, $matches)) {
                        $listeningPorts = array_unique($matches[1]);
                        sort($listeningPorts);
                    }
                }
            }
        }
        
        if (isset($apiResult['tried_endpoints']) && is_array($apiResult['tried_endpoints']) && !empty($apiResult['tried_endpoints'])) {
            $triedList = implode(', ', array_slice($apiResult['tried_endpoints'], 0, 3));
            if (count($apiResult['tried_endpoints']) > 3) {
                $triedList .= ' (and ' . (count($apiResult['tried_endpoints']) - 3) . ' more)';
            }
            $lastError .= " (tried endpoints: {$triedList})";
        } elseif (isset($apiResult['url'])) {
            $lastError .= " (tried: {$apiResult['url']})";
        }
        
        // Add helpful troubleshooting info
        if (!$fppRunning) {
            $lastError .= ". FPP daemon (fppd) does not appear to be running. Start it with: sudo systemctl start fppd";
        } else {
            $lastError .= ". FPP daemon is running but API is not accessible on the tried ports";
            
            // Try to detect what port FPP might be listening on
            if (function_exists('shell_exec')) {
                $listeningPorts = @shell_exec("netstat -tulpn 2>/dev/null | grep -i 'fppd\\|lighttpd\\|nginx' | grep LISTEN | awk '{print $4}' | awk -F: '{print $NF}' | sort -u 2>/dev/null");
                if (empty($listeningPorts)) {
                    // Try alternative method (works on systems without netstat)
                    $listeningPorts = @shell_exec("ss -tulpn 2>/dev/null | grep -i 'fppd\\|lighttpd\\|nginx' | grep LISTEN | awk '{print $5}' | awk -F: '{print $NF}' | sort -u 2>/dev/null");
                }
                if (empty($listeningPorts)) {
                    // Try lsof as last resort
                    $listeningPorts = @shell_exec("lsof -iTCP -sTCP:LISTEN -n -P 2>/dev/null | grep -i 'fppd\\|lighttpd\\|nginx' | awk '{print $9}' | awk -F: '{print $NF}' | sort -u 2>/dev/null");
                }
                
                if (!empty($listeningPorts)) {
                    $ports = array_filter(array_map('trim', explode("\n", trim($listeningPorts))));
                    if (!empty($ports)) {
                        $lastError .= ". FPP may be listening on port(s): " . implode(', ', $ports);
                    } else {
                        $lastError .= ". Could not detect FPP listening port. Check: netstat -tulpn | grep -i fppd";
                    }
                } else {
                    $lastError .= ". Could not detect FPP listening port. Check FPP web interface or run: netstat -tulpn | grep -i fppd";
                }
            }
        }
        
        if (isset($apiResult['endpoint'])) {
            $lastError .= " (last endpoint: {$apiResult['endpoint']})";
        }
    }
    
    if ($apiData) {
        // Got status from HTTP API
        $fppStatus['status_name'] = $apiData['status_name'] ?? 'unknown';
        
        // Handle current_playlist - can be string or object
        $currentPlaylist = $apiData['current_playlist'] ?? '';
        if (is_array($currentPlaylist)) {
            $fppStatus['current_playlist'] = $currentPlaylist['playlist'] ?? $currentPlaylist['name'] ?? '';
        } else {
            $fppStatus['current_playlist'] = $currentPlaylist;
        }
        
        $fppStatus['current_sequence'] = $apiData['current_sequence'] ?? '';
        $fppStatus['seconds_elapsed'] = $apiData['seconds_played'] ?? $apiData['seconds_elapsed'] ?? 0;
        $fppStatus['seconds_remaining'] = $apiData['seconds_remaining'] ?? 0;
        $fppStatus['volume'] = $apiData['volume'] ?? 0;
        
        // Determine playing state - check multiple fields
        // FPP status codes: 0=idle, 1=playing, 2=paused, 3=stopped
        $statusCode = isset($apiData['status']) ? (int)$apiData['status'] : 0;
        $statusName = strtolower($fppStatus['status_name']);
        $isPlaying = isset($apiData['playing']) ? (bool)$apiData['playing'] : false;
        
        // Check if playing: status code 1, or status_name contains 'playing', or playing flag is true
        $fppStatus['playing'] = ($statusCode === 1 || 
                                 $statusName === 'playing' || 
                                 $statusName === 'play' ||
                                 $isPlaying === true ||
                                 (strpos($statusName, 'play') !== false && strpos($statusName, 'stop') === false));
        
        $fppStatus['status_text'] = 'FPP Available';
        $fppStatus['error_detail'] = '';
        
        $result['fpp_status'] = $fppStatus;
        $result['control_method'] = 'MQTT (Status via MQTT)';
        
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
    
    // Fallback to MQTT if HTTP API failed
    if (!$apiData && function_exists('shell_exec')) {
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
    error_msg = "paho-mqtt not installed. Install with: python3 -m pip install paho-mqtt --user"
    print(json.dumps({"error": error_msg, "available": False, "install_command": "python3 -m pip install paho-mqtt --user"}))
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
        
        // Try with timeout first, then without if timeout doesn't exist
        $hasTimeout = @shell_exec('which timeout 2>/dev/null');
        
        if ($hasTimeout) {
            $command = "timeout 3 python3 -W ignore -c " . escapeshellarg($pythonStatusScript) . " " . 
                       escapeshellarg($mqttBroker) . " " . 
                       escapeshellarg($mqttPort) . " " . 
                       escapeshellarg($mqttTopicPrefix) . " 2>&1";
        } else {
            // No timeout command, run directly (with Python timeout in script)
            $command = "python3 -W ignore -c " . escapeshellarg($pythonStatusScript) . " " . 
                       escapeshellarg($mqttBroker) . " " . 
                       escapeshellarg($mqttPort) . " " . 
                       escapeshellarg($mqttTopicPrefix) . " 2>&1";
        }
        
        $output = @shell_exec($command);
    
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
                    
                    // Determine playing state from MQTT status (same logic as HTTP API)
                    $mqttStatusCode = isset($mqttStatus['status']) ? (int)$mqttStatus['status'] : 0;
                    $mqttStatusName = strtolower($fppStatus['status_name']);
                    $fppStatus['playing'] = ($mqttStatusCode === 1 || 
                                             $mqttStatusName === 'playing' || 
                                             $mqttStatusName === 'play' ||
                                             (strpos($mqttStatusName, 'play') !== false && strpos($mqttStatusName, 'stop') === false));
                    
                    $fppStatus['status_text'] = 'FPP Available';
                    $fppStatus['error_detail'] = '';
                } elseif (isset($mqttStatus['timeout']) && $mqttStatus['timeout']) {
                    $fppStatus['status_text'] = 'FPP Not Responding';
                    $fppStatus['error_detail'] = 'MQTT broker reachable but FPP not publishing status.';
                } elseif (isset($mqttStatus['error'])) {
                    $fppStatus['status_text'] = 'MQTT Connection Failed';
                    $errorMsg = $mqttStatus['error'];
                    if (strpos($errorMsg, 'Connection refused') !== false) {
                        $fppStatus['error_detail'] = 'Cannot connect to MQTT broker at ' . $mqttBroker . ':' . $mqttPort . '. Start mosquitto: sudo systemctl start mosquitto';
                    } elseif (strpos($errorMsg, 'paho-mqtt not installed') !== false || strpos($errorMsg, 'paho.mqtt') !== false) {
                        // Show helpful install instructions
                        $installCmd = isset($mqttStatus['install_command']) ? $mqttStatus['install_command'] : 'python3 -m pip install paho-mqtt --user';
                        $fppStatus['error_detail'] = 'paho-mqtt Python package is not installed. Install it with: ' . $installCmd . ' (or reinstall the plugin to install all dependencies)';
                    } else {
                        $fppStatus['error_detail'] = 'MQTT Error: ' . $errorMsg;
                    }
                }
            } else {
                // Failed to parse JSON - might be a Python error
                $fppStatus['status_text'] = 'Status Check Error';
                // Show actual output for debugging
                $cleanOutput = preg_replace('/\s+/', ' ', substr($output, 0, 150));
                $fppStatus['error_detail'] = 'Check failed: ' . $cleanOutput;
            }
        } else {
            // No output - command might have failed
            $fppStatus['status_text'] = 'Status Check Failed';
            $fppStatus['error_detail'] = 'HTTP API failed: ' . $lastError . '. MQTT check also failed. Check if FPP is running and API is accessible.';
        }
    } elseif (!$apiData) {
        // HTTP API failed and shell_exec disabled
        $fppStatus['status_text'] = 'Status Check Disabled';
        $fppStatus['error_detail'] = 'HTTP API failed: ' . $lastError . '. PHP shell_exec is disabled, cannot try MQTT fallback.';
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

    try {
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
                        sleep(2);
                        if (posix_kill($pid, 0)) {
                            posix_kill($pid, $sigkill);
                            sleep(1);
                        }
                    }
                } else {
                    // Fallback: use kill command
                    @exec("kill $pid 2>&1", $output, $return_var);
                    sleep(2);
                    @exec("kill -9 $pid 2>&1", $output, $return_var);
                }
            }
            @unlink($pidFile);
        }

        // Start service using postStart.sh script for consistency
        if (file_exists($startScript)) {
            // Use nohup and background execution to ensure it runs
            $cmd = "cd " . escapeshellarg($pluginDir . '/scripts') . " && bash " . escapeshellarg($startScript) . " 2>&1";
            $output = shell_exec($cmd);

            if ($output) {
                // Check for errors in output
                if (strpos($output, 'ERROR') !== false || strpos($output, 'exit 1') !== false) {
                    $result = array(
                        'status' => 'error',
                        'message' => 'Service failed to start: ' . trim($output)
                    );
                } else {
                    $result = array(
                        'status' => 'restarted',
                        'started' => true,
                        'message' => 'Service restarted successfully'
                    );
                }
            } else {
                // Check if service started successfully by looking for PID file
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
                            $output_ps = array();
                            $return_var = 0;
                            @exec("ps -p $newPid 2>&1", $output_ps, $return_var);
                            if ($return_var === 0 && !empty($output_ps)) {
                                $started = true;
                            }
                        }
                    }
                }

                $result = array(
                    'status' => $started ? 'restarted' : 'restart_initiated',
                    'started' => $started,
                    'message' => $started ? 'Service restarted successfully' : 'Restart initiated, service may still be starting...'
                );
            }
        } elseif (file_exists($script)) {
            // Fallback: start directly
            $python3 = trim(shell_exec("which python3 2>/dev/null"));
            if (empty($python3)) {
                $python3 = 'python3';
            }
            $cmd = "cd " . escapeshellarg($pluginDir . '/scripts') . " && timeout 10 " . escapeshellarg($python3) . " " . escapeshellarg($script) . " 2>&1";
            $output = shell_exec($cmd);

            if ($output && (strpos($output, 'ERROR') !== false || strpos($output, 'ModuleNotFoundError') !== false)) {
                $result = array(
                    'status' => 'error',
                    'message' => 'Service failed to start: ' . trim($output)
                );
            } else {
                $result = array(
                    'status' => 'restarted',
                    'started' => true,
                    'message' => 'Service restarted successfully'
                );
            }
        } else {
            $result = array(
                'status' => 'error',
                'message' => 'Service script not found at: ' . $script
            );
        }
    } catch (Exception $e) {
        $result = array(
            'status' => 'error',
            'message' => 'Exception during restart: ' . $e->getMessage()
        );
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
        // Empty string means auto-detect, store it
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
    error_msg = "paho-mqtt not installed. Install with: python3 -m pip install paho-mqtt --user"
    print(json.dumps({"success": False, "error": error_msg, "install_command": "python3 -m pip install paho-mqtt --user"}))
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

// GET /api/plugin/fpp-Homekit/diagnostics
function fppHomekitDiagnostics() {
    $pluginDir = dirname(__FILE__);
    $scriptsDir = $pluginDir . '/scripts';
    $pidFile = $scriptsDir . '/homekit_service.pid';
    $configFile = $scriptsDir . '/homekit_config.json';
    $pairingInfoFile = $scriptsDir . '/homekit_pairing_info.json';
    
    $result = array(
        'service_running' => false,
        'pid' => null,
        'listen_address' => '',
        'network' => array('interfaces' => array()),
        'port_51826_open' => false,
        'avahi_running' => false,
        'mosquitto_running' => false,
        'mqtt_broker' => 'localhost',
        'mqtt_port' => 1883,
        'setup_code' => null,
        'setup_id' => null,
        'paired' => false
    );
    
    // Check service running
    if (file_exists($pidFile)) {
        $pid = trim(file_get_contents($pidFile));
        if ($pid) {
            $result['pid'] = $pid;
            if (file_exists("/proc/$pid")) {
                $result['service_running'] = true;
            }
        }
    }
    
    // Get listen address
    if (file_exists($configFile)) {
        $config = @json_decode(file_get_contents($configFile), true);
        if ($config && isset($config['homekit_ip'])) {
            $result['listen_address'] = $config['homekit_ip'] ?: 'Auto-detect';
        }
        if ($config && isset($config['mqtt'])) {
            $result['mqtt_broker'] = $config['mqtt']['broker'] ?? 'localhost';
            $result['mqtt_port'] = $config['mqtt']['port'] ?? 1883;
        }
    }
    
    // Get network interfaces
    $interfacesResult = fppHomekitNetworkInterfaces();
    if ($interfacesResult) {
        $interfacesData = json_decode($interfacesResult, true);
        if ($interfacesData && isset($interfacesData['interfaces'])) {
            $result['network']['interfaces'] = $interfacesData['interfaces'];
        }
    }
    
    // Check port 51826
    if (function_exists('shell_exec')) {
        $portCheck = @shell_exec('netstat -tuln 2>/dev/null | grep ":51826 " || ss -tuln 2>/dev/null | grep ":51826 "');
        $result['port_51826_open'] = !empty($portCheck);
    }
    
    // Check avahi-daemon
    if (function_exists('shell_exec')) {
        $avahiCheck = @shell_exec('systemctl is-active avahi-daemon 2>/dev/null');
        $result['avahi_running'] = (trim($avahiCheck) === 'active');
    }
    
    // Check mosquitto
    if (function_exists('shell_exec')) {
        $mosquittoCheck = @shell_exec('systemctl is-active mosquitto 2>/dev/null');
        $result['mosquitto_running'] = (trim($mosquittoCheck) === 'active');
    }
    
    // Get pairing info
    if (file_exists($pairingInfoFile)) {
        $pairingInfo = @json_decode(file_get_contents($pairingInfoFile), true);
        if ($pairingInfo) {
            $result['setup_code'] = $pairingInfo['setup_code'] ?? null;
            $result['setup_id'] = $pairingInfo['setup_id'] ?? null;
        }
    }
    
    // Check if paired
    $stateFile = $scriptsDir . '/homekit_accessory.state';
    if (file_exists($stateFile)) {
        $state = @json_decode(file_get_contents($stateFile), true);
        if ($state && isset($state['paired_clients']) && count($state['paired_clients']) > 0) {
            $result['paired'] = true;
        }
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
    
    // Try multiple methods to get network interfaces
    if (function_exists('shell_exec')) {
        // Method 1: Use 'ip' command (Linux)
        if (command_exists('ip')) {
            $output = @shell_exec('ip -4 -o addr show 2>/dev/null | grep -v "127.0.0.1"');
            if ($output) {
                $lines = explode("\n", trim($output));
                foreach ($lines as $line) {
                    // Parse: "2: eth0    inet 192.168.1.100/24 ..."
                    if (preg_match('/^\d+:\s+(\S+)\s+inet\s+(\d+\.\d+\.\d+\.\d+)/', $line, $matches)) {
                        $ifname = $matches[1];
                        $ip = $matches[2];
                        if ($ifname !== 'lo' && $ip !== '127.0.0.1') {
                            $interfaces[] = array(
                                'name' => $ifname,
                                'ip' => $ip
                            );
                        }
                    }
                }
            }
        }
        
        // Method 2: Try hostname command
        if (empty($interfaces) && command_exists('hostname')) {
            $ip = @shell_exec('hostname -I 2>/dev/null | awk \'{print $1}\'');
            if ($ip && trim($ip) && trim($ip) !== '127.0.0.1') {
                $interfaces[] = array(
                    'name' => 'Primary',
                    'ip' => trim($ip)
                );
            }
        }
        
        // Method 3: Try ifconfig (macOS/BSD)
        if (empty($interfaces) && command_exists('ifconfig')) {
            $output = @shell_exec('ifconfig 2>/dev/null | grep -A 1 "^[a-z]" | grep "inet " | grep -v "127.0.0.1"');
            if ($output) {
                $lines = explode("\n", trim($output));
                $index = 1;
                foreach ($lines as $line) {
                    if (preg_match('/inet\s+(\d+\.\d+\.\d+\.\d+)/', $line, $matches)) {
                        $ip = $matches[1];
                        if ($ip !== '127.0.0.1') {
                            $interfaces[] = array(
                                'name' => 'Interface ' . $index,
                                'ip' => $ip
                            );
                            $index++;
                        }
                    }
                }
            }
        }
    }
    
    // Method 4: PHP socket method (most reliable)
    if (empty($interfaces)) {
        try {
            $sock = @socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
            if ($sock) {
                @socket_connect($sock, '8.8.8.8', 53);
                @socket_getsockname($sock, $ip);
                @socket_close($sock);
                if ($ip && $ip !== '127.0.0.1') {
                    $interfaces[] = array(
                        'name' => 'Primary Interface',
                        'ip' => $ip
                    );
                }
            }
        } catch (Exception $e) {
            // Ignore socket errors
        }
    }
    
    // Remove duplicates
    $seen = array();
    $uniqueInterfaces = array();
    foreach ($interfaces as $iface) {
        $key = $iface['ip'];
        if (!isset($seen[$key])) {
            $uniqueInterfaces[] = $iface;
            $seen[$key] = true;
        }
    }
    $interfaces = $uniqueInterfaces;
    
    // Ensure we always have at least something
    if (empty($interfaces)) {
        // Last resort: Try to get server IP
        if (isset($_SERVER['SERVER_ADDR']) && $_SERVER['SERVER_ADDR'] !== '127.0.0.1') {
            $interfaces[] = array(
                'name' => 'Server IP',
                'ip' => $_SERVER['SERVER_ADDR']
            );
        }
    }
    
    // If no current IP is set, default to the hardwired ethernet interface
    if ($currentIp === null || $currentIp === '') {
        // Find the first ethernet interface (eth0, enp*, ens*, etc.)
        foreach ($interfaces as $iface) {
            $name = strtolower($iface['name']);
            // Prioritize wired interfaces: eth, enp, ens
            if (strpos($name, 'eth') === 0 || 
                strpos($name, 'enp') === 0 || 
                strpos($name, 'ens') === 0 ||
                strpos($name, 'primary') !== false) {
                $currentIp = $iface['ip'];
                break;
            }
        }
        
        // If still not set, use first interface
        if (($currentIp === null || $currentIp === '') && !empty($interfaces)) {
            $currentIp = $interfaces[0]['ip'];
        }
        
        // If still nothing, leave empty for auto-detect
        if ($currentIp === null) {
            $currentIp = '';
        }
    }
    
    $result = array(
        'interfaces' => $interfaces,
        'current_ip' => $currentIp,
        'default_is_ethernet' => !empty($currentIp)
    );
    
    return json($result);
}

function command_exists($cmd) {
    if (!function_exists('shell_exec')) {
        return false;
    }
    $return = @shell_exec(sprintf("which %s 2>/dev/null", escapeshellarg($cmd)));
    return !empty($return);
}

?>
