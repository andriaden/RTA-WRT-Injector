<?php

$config_data = parse_ini_file('/usr/share/rtawrt-injector/settings.ini', true);

function tunnel() {
	exec("nohup python3 /usr/share/rtawrt-injector/tunnel.py > /dev/null 2>&1 &");
	sleep(1);
	exec("nohup python3 /usr/share/rtawrt-injector/ssh.py 1 > /dev/null 2>&1 &");
	saveLog("is connecting to the internet");
	for ($i = 1; $i <= 3; $i++) {
		sleep(3);
		exec("cat logs.txt 2>/dev/null | grep \"CONNECTED SUCCESSFULLY\"|awk '{print $4}'|tail -n1", $var);
		if (implode($var) == "SUCCESSFULLY") {
			exec("screen -dmS GProxy bash -c 'gproxy; exec sh'");
			saveLog("TERHUBUNG!");
			break;
		} else {
			saveLog($i.". Reconnect 3s");
			exec("nohup python3 /usr/share/rtawrt-injector/ssh.py 1 > /dev/null 2>&1 &");
		}
		saveLog("Failed!");
	}
}

function start() {
	if (file_exists("logs-2.txt")) unlink("logs-2.txt");
	saveLog("Menjalankan RTA-WRT Injector");
	if (empty($ssh_host = $config_data['ssh']['host'])) {
		saveLog("Anda Belum Membuat Profile");
	} else {
		stop();
		$sock_mode = $config_data['mode']['sock_mode'];
		if ($sock_mode == "1") {
			exec("route -n | grep -i 0.0.0.0 | head -n1 | awk '{print $2}'", $ipmodem);
			exec('echo "ipmodem='.implode($ipmodem).'" > /usr/share/rtawrt-injector/ipmodem.txt');
			$ssh_host = $config_data['ssh']['host'];
			exec("cat /usr/share/rtawrt-injector/ipmodem.txt | grep -i ipmodem | cut -d= -f2 | tail -n1", $route);
			exec("ip tuntap add dev tun1 mode tun");
			exec("ifconfig tun1 10.0.0.1 netmask 255.255.255.0");
			tunnel();
			exec("route add 8.8.8.8 gw ".implode($route)." metric 0");
			exec("route add 8.8.4.4 gw ".implode($route)." metric 0");
			exec("route add ".$ssh_host." gw ".implode($route)." metric 0");
			exec("route add default gw 10.0.0.2 metric 0");
		} else if ($sock_mode == "2") {
			tunnel();
		}
		exec("rm -r logs.txt 2>/dev/null");
		file_put_contents("/usr/bin/ping-rtawrt-injector", "#!/bin/bash\n#rtawrt-injector\nhttping m.google.com\n");
		exec("chmod +x /usr/bin/ping-rtawrt-injector");
		exec("/usr/bin/ping-rtawrt-injector > /dev/null 2>&1 &");
	}
	file_put_contents("/etc/crontabs/root", "# BEGIN AUTOREKONEK RTA-WRT\n*/1 * * * *  autorekonek-rtawrt-injector\n# END AUTOREKONEK RTA-WRT\n", FILE_APPEND);
	exec("sed -i '/^$/d' /etc/crontabs/root 2>/dev/null");
	exec("/etc/init.d/cron restart");
}

function stop() {
	$sock_mode = $config_data['mode']['sock_mode'];
	exec("screen -S GProxy -X quit");
	if ($sock_mode == "1") {
		$ssh_host = $config_data['ssh']['host'];
		exec("cat /usr/share/rtawrt-injector/ipmodem.txt | grep -i ipmodem | cut -d= -f2 | tail -n1", $route);
		exec("killall -q badvpn-tun2socks ssh ping-rtawrt-injector sshpass httping python3");
		exec('route del 8.8.8.8 gw "'.implode($route).'" metric 0 2>/dev/null');
		exec('route del 8.8.4.4 gw "'.implode($route).'" metric 0 2>/dev/null');
		exec('route del "'.$ssh_host.'" gw "'.implode($route).'" metric 0 2>/dev/null');
		exec("ip link delete tun1 2>/dev/null");
	} else if ($sock_mode == "2") {
		exec("iptables -t nat -F OUTPUT 2>/dev/null");
		exec("iptables -t nat -F PROXY 2>/dev/null");
		exec("iptables -t nat -F PREROUTING 2>/dev/null");
		exec("killall -q redsocks python3 ssh ping-rtawrt-injector sshpass httping fping screen");
	}
	exec("/etc/init.d/dnsmasq restart 2>/dev/null");
	exec('sed -i "/^# BEGIN AUTOREKONEK RTA-WRT/,/^# END AUTOREKONEK RTA-WRT/d" /etc/crontabs/root > /dev/null');
	exec("/etc/init.d/cron restart");
}


function saveConfig() {
    $connection_mode = explode("|", $_POST["connection_mode"]);
	$sock_mode = $_POST["sock_mode"];
	$ssh_host = $_POST["ssh_host"];
	$ssh_port = $_POST["ssh_port"];
	$ssh_udp = $_POST["ssh_udp"];
	$ssh_username = $_POST["ssh_username"];
	$ssh_password = $_POST["ssh_password"];
    $proxy_ip = $_POST["proxy_ip"];
    $proxy_port = $_POST["proxy_port"];
	$sni_server_name = $_POST["sni_server_name"];
	$payload = $_POST["payload"];
	if ($sock_mode == "1") {
		$badvpn = "badvpn-tun2socks --tundev tun1 --netif-ipaddr 10.0.0.2 --netif-netmask 255.255.255.0 --socks-server-addr 127.0.0.1:1080 --udpgw-remote-server-addr 127.0.0.1:".$ssh_udp." --udpgw-connection-buffer-size 65535 --udpgw-transparent-dns &";
	} else if ($sock_mode == "2") {
		file_put_contents("/etc/redsocks.conf", base64_decode("YmFzZSB7Cglsb2dfZGVidWcgPSBvZmY7Cglsb2dfaW5mbyA9IG9mZjsKCXJlZGlyZWN0b3IgPSBpcHRhYmxlczsKfQpyZWRzb2NrcyB7Cglsb2NhbF9pcCA9IDAuMC4wLjA7Cglsb2NhbF9wb3J0ID0gODEyMzsKCWlwID0gMTI3LjAuMC4xOwoJcG9ydCA9IDEwODA7Cgl0eXBlID0gc29ja3M1Owp9CnJlZHNvY2tzIHsKCWxvY2FsX2lwID0gMTI3LjAuMC4xOwoJbG9jYWxfcG9ydCA9IDgxMjQ7CglpcCA9IDEwLjAuMC4xOwoJcG9ydCA9IDEwODA7Cgl0eXBlID0gc29ja3M1Owp9CnJlZHVkcCB7CiAgICBsb2NhbF9pcCA9IDEyNy4wLjAuMTsgCiAgICBsb2NhbF9wb3J0ID0gVURQR1c7CiAgICBpcCA9IDEwLjAuMC4xOwogICAgcG9ydCA9IDEwODA7CiAgICBkZXN0X2lwID0gOC44LjguODsgCiAgICBkZXN0X3BvcnQgPSA1MzsgCiAgICB1ZHBfdGltZW91dCA9IDMwOwogICAgdWRwX3RpbWVvdXRfc3RyZWFtID0gMTgwOwp9CmRuc3RjIHsKCWxvY2FsX2lwID0gMTI3LjAuMC4xOwoJbG9jYWxfcG9ydCA9IDUzMDA7Cn0="));
		if (isset($ssh_udp)) {
			file_put_contents("/etc/redsocks.conf", str_replace("UDPGW", $ssh_udp, file_get_contents("/etc/redsocks.conf")));
		} else {
			error_log("Error: \$ssh_udp is not defined.");
		}
		$badvpn = "#!/bin/bash\n#rtawrt-injector\niptables -t nat -N PROXY 2>/dev/null\niptables -t nat -A PREROUTING -i br-lan -p tcp -j PROXY\niptables -t nat -A PROXY -d 127.0.0.0/8 -j RETURN\niptables -t nat -A PROXY -d 192.168.0.0/16 -j RETURN\niptables -t nat -A PROXY -d 0.0.0.0/8 -j RETURN\niptables -t nat -A PROXY -d 10.0.0.0/8 -j RETURN\niptables -t nat -A PROXY -p tcp -j REDIRECT --to-ports 8123\niptables -t nat -A PROXY -p tcp -j REDIRECT --to-ports 8124\niptables -t nat -A PROXY -p udp --dport 53 -j REDIRECT --to-ports ".$ssh_udp."\nredsocks -c /etc/redsocks.conf -p /var/run/redsocks.pid &";
	}
	file_put_contents("/usr/bin/gproxy", $badvpn."\n");
	exec("chmod +x /usr/bin/gproxy");
	if ($connection_mode[0] !== "mode-http") {
		$sProxy = "proxyip = \nproxyport = ";
		$proxy_ip = "-";
    	$proxy_port = "-";
	} else {
		$sProxy = "proxyip = ".$proxy_ip."\nproxyport = ".$proxy_port;
	}
	file_put_contents("/usr/share/rtawrt-injector/settings.ini", "[mode]\n\nconnection_mode = ".$connection_mode[1]."\nsock_mode = ".$sock_mode."\n\n[config]\npayload = ".$payload."\n".$sProxy."\n\nauto_replace = 1\n\n[ssh]\nhost = ".$ssh_host."\nport = ".$ssh_port."\nusername = ".$ssh_username."\npassword = ".$ssh_password."\nudp = ".$ssh_udp."\n\n[sni]\nserver_name = ".$sni_server_name."\n");
	echo "Sett Profile Sukses";
}

function saveLog($str) {
	$str = "[".date("H:i:s")."] ".$str."\n";
	file_put_contents("logs-2.txt", $str, FILE_APPEND);
	echo $str;
}

$action = $_POST["action"];
switch ($action) {
	case "start";
		start();
		break;
	case "stop";
		if (file_exists("logs-2.txt")) unlink("logs-2.txt");
		saveLog("Menghentikan RTA-WRT Injector");
		stop();
		saveLog("Stop Sukses");
		break;
	case "saveConfig";
		saveConfig();
		break;
}
?>
