#!/bin/bash

clear

LOG_FILE="/usr/share/rtawrt-injector/logs-2.txt"

SaveLog() {
    local message="$1"
    local timestamp=$(date +"[%H:%M:%S]")
    printf "%s %s\n" "$timestamp" "$message" >> "$LOG_FILE"
}

killproc() {
    local process_names="$1"
    IFS=' ' read -r -a processes <<< "$process_names"
    for process_name in "${processes[@]}"; do
        local pids=$(pgrep -f "$process_name")
        if [ -n "$pids" ]; then
            kill -9 $pids
        fi
    done
}

readini() {
    local section="$1"
    local key="$2"
    local ini_file="/usr/share/rtawrt-injector/settings.ini"
    local current_section=""
    local value=""

    if [ ! -f "$ini_file" ]; then
        SaveLog "File not found: $ini_file"
        return 1
    fi

    while IFS= read -r line; do
        line=$(echo "$line" | xargs)
        if [[ $line =~ ^\[(.*)\]$ ]]; then
            current_section="${BASH_REMATCH[1]}"
        elif [[ $line =~ ^([^=]+)=(.*)$ ]]; then
            local current_key="${BASH_REMATCH[1]}"
            value="${BASH_REMATCH[2]}"
            current_key=$(echo "$current_key" | xargs)
            value=$(echo "$value" | xargs)
            if [[ "$current_section" == "$section" && "$current_key" == "$key" ]]; then
                echo "$value"
                return 0
            fi
        fi
    done < "$ini_file"

    SaveLog "Key '$key' not found in section '[$section]'."
    return 1
}

writeini() {
    local section="$1"
    local key="$2"
    local value="$3"
    local ini_file="/usr/share/rtawrt-injector/settings.ini"
    local temp_file="/tmp/settings.ini.tmp"
    local in_section=0
    local key_found=0

    if [ ! -f "$ini_file" ]; then
        SaveLog "File not found: $ini_file"
        return 1
    fi

    while IFS= read -r line || [ -n "$line" ]; do
        if [[ $line =~ ^\[(.*)\]$ ]]; then
            if [[ "${BASH_REMATCH[1]}" == "$section" ]]; then
                in_section=1
            else
                in_section=0
            fi
        elif [[ $in_section -eq 1 && $line =~ ^([^=]+)=(.*)$ ]]; then
            if [[ "${BASH_REMATCH[1]}" == "$key" ]]; then
                echo "$key=$value" >> "$temp_file"
                key_found=1
                continue
            fi
        fi
        echo "$line" >> "$temp_file"
    done < "$ini_file"
    if [[ $key_found -eq 0 ]]; then
        if ! grep -q "^\[$section\]" "$ini_file"; then
            echo "[$section]" >> "$temp_file"
        fi
        echo "$key=$value" >> "$temp_file"
    fi
    mv "$temp_file" "$ini_file"
}


STARTUP_FILE="/etc/rc.local"
STARTUP_MARKER="####### RTA-WRT INJECTOR STARTUP #######"
STARTUP_CMD="sleep 5 && nohup /usr/share/rtawrt-injector/rtawrt.sh start > /dev/null 2>&1 &"
END_MARKER="########################################"

add_startup() {
    if grep -q "$STARTUP_MARKER" "$STARTUP_FILE"; then
        SaveLog "Startup command already exists in $STARTUP_FILE."
    else
        sed -i "/exit 0/i $STARTUP_MARKER\n$STARTUP_CMD\n$END_MARKER\n" "$STARTUP_FILE"
        SaveLog "Startup command added to $STARTUP_FILE."
    fi
}

remove_startup() {
    sed -i "/$STARTUP_MARKER/,/$END_MARKER/d" "$STARTUP_FILE"
    SaveLog "Startup command removed from $STARTUP_FILE."
}


tunnel() {
    nohup python3 /usr/share/rtawrt-injector/tunnel.py > /dev/null 2>&1 &
    sleep 1
    nohup python3 /usr/share/rtawrt-injector/ssh.py 1 > /dev/null 2>&1 &
    SaveLog "is connecting to the internet"

    local maxReconnectAttempts=5  # Set maximum reconnect attempts
    local attempt=0

    while true; do
        sleep 3
        var=$(grep "CONNECTED SUCCESSFULLY" /usr/share/rtawrt-injector/logs.txt 2>/dev/null | awk '{print $4}' | tail -n1)

        if [ "$var" = "SUCCESSFULLY" ]; then 
            tun2sock_mode=$(readini "mode" "tun2socks")
            if [[ $tun2sock_mode = "1" ]]; then
                UDP=$(readini "ssh" "udpgw")
                /usr/share/rtawrt-injector/tun2socks.sh $UDP &
            fi
            SaveLog "Connection established successfully!"
            autoReconnect=$(readini "mode" "autoReconnect")
            if [[ $autoReconnect = "1" ]]; then
                SaveLog "Auto Reconnect Enabled!"
                if [[ "$2" != "reconnect" ]]; then
                    nohup /usr/share/rtawrt-injector/autoreconnect.sh > /dev/null 2>&1 &
                fi
            fi
            pingLoop=$(readini "mode" "pingLoop")
            if [[ $pingLoop = "1" ]]; then
                SaveLog "Auto pingLoop Enabled!"
                nohup /usr/share/rtawrt-injector/pingloop.sh > /dev/null 2>&1 &
            fi
            SaveLog "RTA-WRT Status Connected..."
            add_startup
            break
        else
            attempt=$((attempt + 1))
            SaveLog "Failed to connect. Reconnecting SSH (Attempt $attempt)"
            nohup python3 /usr/share/rtawrt-injector/ssh .py 1 > /dev/null 2>&1 &
        fi

        sleep 1
    done
}

start() {
    rm -r /usr/share/rtawrt-injector/logs.txt 2>/dev/null
    rm -r /usr/share/rtawrt-injector/logs-2.txt 2>/dev/null
    SaveLog "Starting the process..."
    tun2sock_mode=$(readini "mode" "tun2socks")
    if [[ $tun2sock_mode = "1" ]]; then
        ipmodem=$(route -n | grep -i 0.0.0.0 | head -n1 | awk '{print $2}') 
        serverhost=$(readini "ssh" "serverHost")
        ip tuntap add dev tun1 mode tun
        ifconfig tun1 10.0.0.1 netmask 255.255.255.0
        tunnel
        route add 8.8.8.8 gw $ipmodem metric  0
        route add 8.8.4.4 gw $ipmodem metric 0
        route add $serverhost gw $ipmodem metric 0
        route add default gw 10.0.0.2 metric 0
    else
        tunnel
    fi
}

stop() {
    pingLoop=$(readini "mode" "pingLoop")
    if [[ $pingLoop = "1" ]]; then
        SaveLog "Auto pingLoop Disabled!"
        killproc "pingloop.sh"
    fi
    autoReconnect=$(readini "mode" "autoReconnect")
    if [[ $autoReconnect = "1" ]]; then
        SaveLog "Auto reconnect Disabled!"
        if [[ "$2" != "reconnect" ]]; then
            killproc "autoreconnect.sh"
        fi
    fi
    tun2sock_mode=$(readini "mode" "tun2socks")
    if [[ $tun2sock_mode = "1" ]]; then
        ipmodem=$(route -n | grep -i 0.0.0.0 | head -n1 | awk '{print $2}') 
        serverhost=$(readini "ssh" "serverHost")
        route del 8.8.8.8 gw "$ipmodem" metric 0 2>/dev/null
        route del 8.8.4.4 gw "$ipmodem" metric 0 2>/dev/null
        route del "$serverhost" gw "$ipmodem" metric 0 2>/dev/null
        ip link delete tun1 2>/dev/null
    fi
    killproc "ssh sshpass corkscrew ssh.py tunnel.py"
    /etc/init.d/dnsmasq restart 2>/dev/null
    SaveLog "Stopped RTA-WRT Sukses..."
    remove_startup
    rm -r /usr/share/rtawrt-injector/logs.txt 2>/dev/null
    killproc "rtawrt.sh"
}

if [ $# -eq 0 ]; then
    SaveLog "Usage: $0 <start|stop>"
    exit 1
fi

case "$1" in
    start)
        start "$2"
        ;;
    stop)
        stop "$2"
        ;;
    *)
        SaveLog "Invalid argument: $1"
        SaveLog "Usage: $0 <start|stop>"
        exit 1
        ;;
esac