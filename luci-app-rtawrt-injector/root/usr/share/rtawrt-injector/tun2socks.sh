#!/bin/bash
#rtawrt-injector

TUN="tun1"
UDP=$1

badvpn-tun2socks --tundev $TUN --netif-ipaddr 10.0.0.2 --netif-netmask 255.255.255.0 --socks-server-addr 127.0.0.1:2505 --udpgw-remote-server-addr 127.0.0.1:$UDP --udpgw-connection-buffer-size 65535 --udpgw-transparent-dns &