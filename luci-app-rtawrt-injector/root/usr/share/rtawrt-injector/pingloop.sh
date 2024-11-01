#!/bin/bash

LOG_FILE="/usr/share/rtawrt-injector/logs-2.txt"

SaveLog() {
    local message="$1"
    local timestamp=$(date +"[%H:%M:%S]")
    printf "%s %s\n" "$timestamp" "$message" >> "$LOG_FILE"
}


URL="https://api.ipify.org"
while true; do
    if curl -L --socks5-hostname 127.0.0.1:2505 "$URL" > /dev/null 2>&1; then
        SaveLog "Ping Success"
        #SaveLog "[$TIMESTAMP] IP Address: $(curl -L --socks5-hostname 127.0.0.1:2505 $URL)"
    else
        SaveLog "Ping Not Success | Error"
    fi
    sleep 2
done