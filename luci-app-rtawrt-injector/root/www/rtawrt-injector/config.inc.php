<?php
if (!file_exists("logs-2.txt")) touch("logs-2.txt");
$log = file_get_contents("logs-2.txt");
if (preg_match('/TERHUBUNG/', $log)) $terhubung = true;

// Define the path to the INI file
$file_path = '/usr/share/rtawrt-injector/settings.ini';

// Parse the INI file with sections
$config_data = parse_ini_file($file_path, true);

if ($config_data === false) {
    die("Error reading INI file.");
}

// Accessing data from each section
$connection_mode = $config_data['mode']['connection_mode'];
$sock_mode = $config_data['mode']['sock_mode'];
$payload = $config_data['config']['payload'];
$proxy_ip = $config_data['config']['proxyip'];
$proxy_port = $config_data['config']['proxyport'];
$auto_replace = $config_data['config']['auto_replace'];
$ssh_host = $config_data['ssh']['host'];
$ssh_port = $config_data['ssh']['port'];
$ssh_username = $config_data['ssh']['username'];
$ssh_password = $config_data['ssh']['password'];
$ssh_udp = $config_data['ssh']['udp'];
$sni_server_name = $config_data['sni']['server_name'];

$account = '';
if (!empty($ssh_host) && !empty($ssh_port) && !empty($ssh_username) && !empty($ssh_password)) {
    $account = "$ssh_host:$ssh_port@$ssh_username:$ssh_password";
}

$payload2 = '';
if (!empty($payload)) {
    $payload2 = $payload;
}

$sni2 = '';
if (!empty($sni_server_name)) {
    $sni2 = $sni_server_name;
}

$proxy2 = '';
if (!empty($proxy_ip) && !empty($proxy_port)) {
    $proxy2 = "$proxy_ip:$proxy_port";
}
?>