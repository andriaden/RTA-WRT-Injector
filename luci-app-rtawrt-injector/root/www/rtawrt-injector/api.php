<?php

function json_response($data) {
	$resp = array(
		'status' => 'OK',
		'data' => $data
	);
	header("Content-Type: application/json; charset=UTF-8");
	echo json_encode($resp, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}


function startLog() {
    $logFile = '/usr/share/rtawrt-injector/logs-2.txt';
    if (!file_exists($logFile)) {
        file_put_contents($logFile, '');
    }
    $datalog = file_get_contents($logFile);
    json_response($datalog);
}

function startTunnel() {
    // Hapus log sebelumnya
    file_put_contents('/usr/share/rtawrt-injector/logs-2.txt', '');

    // Jalankan tunnel
    exec("nohup /usr/share/rtawrt-injector/rtawrt.sh start > /dev/null 2>&1 &");
    $logFile = '/usr/share/rtawrt-injector/logs-2.txt';

    $startTime = time();
    $timeout = 30; // Batas waktu 30 detik
    $status = 'FAILED';

    while (time() - $startTime < $timeout) {
        if (file_exists($logFile)) {
            $logContent = file_get_contents($logFile);

            // Tambahkan beberapa kondisi status
            if (
                strpos($logContent, 'is connecting to the internet') !== false
            ) {
                $status = 'CONNECTED';
                break;
            }

            // Cek jika ada error
            if (strpos($logContent, 'Error:') !== false) {
                $status = 'ERROR';
                break;
            }
        }
        usleep(500000); // Kurangi beban CPU dengan sleep 0.5 detik
    }

    // Update status di konfigurasi
    exec("uci set rtawrt-injector.main.status=CONNECTED");
    exec("uci commit rtawrt-injector");

    $output = [
        'status' => $status,
        'message' => $status === 'CONNECTED' ? 'Tunnel berhasil dimulai' : 'Gagal memulai tunnel'
    ];
    json_response($output);
}

function stopTunnel() {
    // Jalankan perintah stop
    exec("nohup /usr/share/rtawrt-injector/rtawrt.sh stop > /dev/null 2>&1 &");
    $logFile = '/usr/share/rtawrt-injector/logs-2.txt';

    $startTime = time();
    $timeout = 20; // Batas waktu 20 detik
    $status = 'FAILED';

    while (time() - $startTime < $timeout) {
        if (file_exists($logFile)) {
            $logContent = file_get_contents($logFile);

            if (
                strpos($logContent, 'Stopped RTA-WRT Sukses...') !== false
            ) {
                $status = 'STOPPED';
                break;
            }

            // Cek jika ada error
            if (strpos($logContent, 'Error:') !== false) {
                $status = 'ERROR';
                break;
            }
        }
        usleep(500000); // Kurangi beban CPU dengan sleep 0.5 detik
    }

    // Update status di konfigurasi
    exec("uci set rtawrt-injector.main.status=STOPPED");
    exec("uci commit rtawrt-injector");

    $output = [
        'status' => $status,
        'message' => $status === 'STOPPED' ? 'Tunnel berhasil dihentikan' : 'Gagal menghentikan tunnel'
    ];
    json_response($output);
}

function getStatus() {
    $inifile = parse_ini_file('/usr/share/rtawrt-injector/settings.ini', true); 
	exec("uci get rtawrt-injector.main.status", $STATUS);
    $responseData = [
        'status' => implode($STATUS)
    ];
	json_response($responseData);
}

function updateStatus() {
	$json = json_decode(file_get_contents('php://input'), true);
	$data = $json['data']['status'];
	exec("uci set rtawrt-injector.main.status=${data}");
	exec("uci commit rtawrt-injector");
}

function getConfig() {
	header('Content-Type: application/json');
    $filePath = '/usr/share/rtawrt-injector/settings.ini';

    if (file_exists($filePath)) {
        $configData = parse_ini_file($filePath, true); 

        header('Content-Type: application/json');

        $responseData = [
            'tun2socks' => $configData['mode']['tun2socks'],
            'memoryCleaner' => $configData['mode']['memoryCleaner'],
            'autoReconnect' => $configData['mode']['autoReconnect'],
            'pingLoop' => $configData['mode']['pingLoop'],
            'mode' => $configData['config']['mode'],
			'modeconfig' => $configData['config']['modeconfig'],
			'enableHttpProxy' => $configData['config']['enableHttpProxy'],
            'payload' => $configData['config']['payload'],
            'proxyServer' => $configData['config']['proxyServer'],
            'proxyPort' => $configData['config']['proxyPort'],
            'serverHost' => $configData['ssh']['serverHost'],
            'serverPort' => $configData['ssh']['serverPort'],
            'username' => $configData['ssh']['username'],
            'password' => $configData['ssh']['password'],
			'udpgw' => $configData['ssh']['udpgw'],
            'sni' => $configData['sni']['server_name']
        ];

		json_response($responseData);
    } else {
		header('HTTP/1.1 404 Not Found');
        echo json_encode(['status' => 'error', 'message' => 'File not found.']);
    }
}


function saveConfig() {
	$json = json_decode(file_get_contents('php://input'), true);

	$tun2socks = $json['data']['tun2socks'];
	$memoryCleaner = $json['data']['memoryCleaner'];
	$autoReconnect = $json['data']['autoReconnect'];
	$pingLoop = $json['data']['pingLoop'];
	$mode = $json['data']['mode'];
	$modeconfig = $json['data']['modeconfig'];
	$enableHttpProxy = $json['data']['enableHttpProxy'];
	$payload = $json['data']['payload'];
	$proxyServer = $json['data']['proxyServer'];
	$proxyPort = $json['data']['proxyPort'];
	$serverHost = $json['data']['serverHost'];
	$serverPort = $json['data']['serverPort'];
	$username = $json['data']['username'];
	$password = $json['data']['password'];
	$udpgw = $json['data']['udpgw'];
	$sni = $json['data']['sni'];

    $data = "[mode]\n";
    $data .= "tun2socks = $tun2socks\n";
    $data .= "memoryCleaner = $memoryCleaner\n";
    $data .= "autoReconnect = $autoReconnect\n";
    $data .= "pingLoop = $pingLoop\n\n";
    $data .= "[config]\n";
    $data .= "mode = $mode\n";
	$data .= "modeconfig = $modeconfig\n";
    $data .= "enableHttpProxy = $enableHttpProxy\n";
    $data .= "payload = $payload\n";
    $data .= "proxyServer = $proxyServer\n";
    $data .= "proxyPort = $proxyPort\n\n";
    $data .= "auto_replace = 1\n\n";
    $data .= "[ssh]\n";
    $data .= "serverHost = $serverHost\n";
    $data .= "serverPort = $serverPort\n";
    $data .= "username = $username\n";
    $data .= "password = $password\n";
	$data .= "udpgw = $udpgw\n\n";
    $data .= "[sni]\n";
    $data .= "server_name = $sni\n";

    $file_path = '/usr/share/rtawrt-injector/settings.ini';


    if (file_put_contents($file_path, $data) !== false) {
		header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'message' => 'File saved successfully.']);
    } else {
		header('HTTP/1.1 404 Not Found');
        echo json_encode(['status' => 'error', 'message' => 'Failed to save file.']);
    }
}


$json = json_decode(file_get_contents('php://input'), true);
switch ($json['action']) {
	case "log":
		startLog();
		break;
    case "startTunnel":
        startTunnel();
        break;
    case "stopTunnel":
        stopTunnel();
        break;
	case "getStatus":
		getStatus();
		break;
	case "updateStatus":
		updateStatus();
		break;
    case "saveConfig":
        saveConfig();
        break;
    case "getConfig":
        getConfig();
        break;
    case "cleanLog";
        file_put_contents('/usr/share/rtawrt-injector/logs-2.txt', "[".date("H:i:s")."] Clear Log. Sucess");
        break;
}
?>