#!/bin/bash

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


while true; do
    route2="$(netstat -plantu | grep -i ssh | grep -i 2505 | grep -i listen)" 
    route3="$(netstat -plantu | grep corkscrew)" 
    route4="$(netstat -plantu | grep ssh | grep CLOSE_WAIT | awk '{print $6}' | wc -l)" 

    if [[ -z $route2 ]]; then
        SaveLog "Reconecting STARTED."
        nohup /usr/share/rtawrt-injector/rtawrt.sh stop reconnect > /dev/null 2>&1 &
        sleep 1
        nohup /usr/share/rtawrt-injector/rtawrt.sh start reconnect > /dev/null 2>&1 &
        break
    elif [[ -z $route3 ]]; then
        SaveLog "Reconecting STARTED."
        nohup /usr/share/rtawrt-injector/rtawrt.sh stop reconnect > /dev/null 2>&1 &
        sleep 1
        nohup /usr/share/rtawrt-injector/rtawrt.sh start reconnect > /dev/null 2>&1 &
        break
    elif [[ $route4 -gt 10 ]]; then
        SaveLog "Reconecting STARTED."
        nohup /usr/share/rtawrt-injector/rtawrt.sh stop reconnect > /dev/null 2>&1 &
        sleep 1
        nohup /usr/share/rtawrt-injector/rtawrt.sh start reconnect > /dev/null 2>&1 &
        break
    fi
    sleep 1
done


