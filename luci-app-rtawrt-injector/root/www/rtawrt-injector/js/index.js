var modeconfig;

function handleModeChange() {
    const mode = document.getElementById('mode').value;
    const sniField = document.getElementById('sniField');
    const payloadField = document.getElementById('payloadField');
    const enableHttpProxyField = document.getElementById('enableHttpProxyField');
    const proxyFields = document.getElementById('proxyFields');

    if (mode === 'SSH') {
        sniField.classList.add('hidden');
        payloadField.classList.remove('hidden');
        enableHttpProxyField.classList.remove('hidden');
        const HTTPPROXY = document.getElementById('enableHttpProxy').checked;
        handleHttpProxyChange();
        modeconfig = HTTPPROXY ? "1" : "0";
    } else if (mode === 'SSH - SSL') {
        sniField.classList.remove('hidden');
        payloadField.classList.add('hidden');
        enableHttpProxyField.classList.add('hidden');
        proxyFields.classList.add('hidden');
        modeconfig = "2";
    } else if (mode === 'SSH - WS - CDN') {
        sniField.classList.remove('hidden');
        payloadField.classList.remove('hidden');
        enableHttpProxyField.classList.add('hidden');
        proxyFields.classList.add('hidden');
        modeconfig = "3";
    }
}

function handleHttpProxyChange() {
    const enableHttpProxy = document.getElementById('enableHttpProxy').checked;
    const proxyFields = document.getElementById('proxyFields');

    if (enableHttpProxy) {
        proxyFields.classList.remove('hidden');
    } else {
        proxyFields.classList.add('hidden');
    }
}

function disableInputs(disable) {
    const inputs = document.querySelectorAll('input, select, textarea');
    const saveButton = document.getElementById('saveButton');
    inputs.forEach(input => {
        if (!['home', 'log', 'config', 'about'].includes(input.id)) {
            input.disabled = disable;
            saveButton.hidden = disable;
        }
    });
}

async function loadStatus() {
    try {
        const response = await axios.post('api.php', {
            action: 'getStatus'
        });
        const statusData = response.data.data;
        const startButton = document.getElementById('startButton');
        const stopButton = document.getElementById('stopButton');
        
        if (statusData.status === 'running') {
            startButton.hidden = true;
            stopButton.hidden = false;
            disableInputs(true);
            document.getElementById('statusOverwiew').innerHTML = "Status: Connected";
        } else {
            startButton.hidden = false;
            stopButton.hidden = true;
            disableInputs(false);
            document.getElementById('statusOverwiew').innerHTML = "Status: Stoped";
        }
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Failed to load status.'
        });
    }
}

async function handleStartButton() {
    const startButton = document.getElementById('startButton');
    const stopButton = document.getElementById('stopButton');

    const confirmStart = await Swal.fire({
        title: 'Start Process',
        text: "Do you want to start the process?",
        icon: 'info',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, start it!'
    });

    startButton.innerHTML = 'Start';
    disableInputs(false);
    if (confirmStart.isConfirmed) {
        try {
            if (document.getElementById('serverHost').value === '') {
                throw new Error('Server Host is required.');
            }
            await handleSaveButton('silent');
            startButton.innerHTML = '<i class="fa fa-spinner fa-spin mr-2"></i>Connecting';
            startButton.disabled = true;
            disableInputs(true);
            await axios.post('api.php', {
                action: "startTunnel"
            }).then(function (response) {
                setTimeout(() => {
                    stopButton.innerHTML = 'Stop';
                    startButton.hidden = true;
                    stopButton.hidden = false;
                    updateStatus('running');
                    loadStatus();
                }, 3000);
            });
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message
            });
            disableInputs(false);
            startButton.innerHTML = 'Start';
            startButton.disabled = false;
            await updateStatus('stopped');
            loadStatus();
        }
    }
}

async function handleStopButton() {
    const startButton = document.getElementById('startButton');
    const stopButton = document.getElementById('stopButton');

    const confirmStop = await Swal.fire({
        title: 'Are you sure?',
        text: "You want to stop the process?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, stop it!'
    });

    stopButton.innerHTML = 'Stop';
    disableInputs(false);
    if (confirmStop.isConfirmed) {
        try {
            CleanLog();
            stopButton.innerHTML = '<i class="fa fa-spinner fa-spin mr-2"></i>Stopping';
            stopButton.disabled = true;
            await axios.post('api.php', {
                action: "stopTunnel"
            }).then(function (response) {
                if (response.data.data.status === 'STOPPED') {
                    disableInputs(false);
                    startButton.innerHTML = 'Start';
                    startButton.hidden = false;
                    startButton.disabled = false;
                    stopButton.hidden = true;
                    stopButton.disabled = false;
                    updateStatus('stopped');
                    loadStatus();
                }
            });
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to stop the process.'
            });
            disableInputs(true);
            stopButton.innerHTML = 'Stop';
            stopButton.disabled = false;
            await updateStatus('running');
            loadStatus();
        }
    }
}

async function updateStatus(status) {
    try {
        axios.post('api.php', {
            action: 'updateStatus',
            data: { 
                status: status
            }
        });
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Failed to update status.'
        });
    }
}

async function handleSaveButton(options) {
    const saveButton = document.getElementById('saveButton');
    saveButton.innerHTML = '<i class="fa fa-spinner fa-spin mr-2"></i>Saving';
    saveButton.disabled = true;

    try {
        if (document.getElementById('serverHost').value === '') {
            throw Error;
        }
        axios.post('api.php', {
            action: "saveConfig",
            data: {
                tun2socks: document.getElementById('tun2socks').checked,
                memoryCleaner: document.getElementById('memoryCleaner').checked,
                autoReconnect: document.getElementById('autoReconnect').checked,
                pingLoop: document.getElementById('pingLoop').checked,
                mode: document.getElementById('mode').value,
                modeconfig: modeconfig,
                serverHost: document.getElementById('serverHost').value,
                serverPort: document.getElementById('serverPort').value,
                username: document.getElementById('username').value,
                password: document.getElementById('password').value,
                udpgw: document.getElementById('udpgw').value,
                enableHttpProxy: document.getElementById('enableHttpProxy').checked,
                payload: document.getElementById('payload').value,
                proxyServer: document.getElementById('proxyServer').value,
                proxyPort: document.getElementById('proxyPort').value,
                sni: document.getElementById('sni').value
            }
        });

        if (options !== 'silent') {
            Swal.fire({
                icon: 'success',
                title: 'Saved',
                text: 'The configuration has been saved successfully.'
            });
        }

        saveButton.innerHTML = 'Save';
        saveButton.disabled = false;
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Failed to save the configuration.'
        });
        saveButton.innerHTML = 'Save';
        saveButton.disabled = false;
    }
}

async function loadConfigData() {
    try {
        // API call to load config data
        var response = await axios.post('api.php', {
            action: 'getConfig'
        });

        var configData = response.data;
        if (configData.status === 'OK') {
            document.getElementById('tun2socks').checked = configData.data.tun2socks;
            document.getElementById('memoryCleaner').checked = configData.data.memoryCleaner;
            document.getElementById('autoReconnect').checked = configData.data.autoReconnect;
            document.getElementById('pingLoop').checked = configData.data.pingLoop;
            document.getElementById('mode').value = configData.data.mode;
            modeconfig = configData.data.modeconfig;
            document.getElementById('serverHost').value = configData.data.serverHost;
            document.getElementById('serverPort').value = configData.data.serverPort;
            document.getElementById('username').value = configData.data.username;
            document.getElementById('password').value = configData.data.password;
            document.getElementById('udpgw').value = configData.data.udpgw;
            document.getElementById('enableHttpProxy').checked = configData.data.enableHttpProxy;
            document.getElementById('payload').value = configData.data.payload;
            document.getElementById('proxyServer').value = configData.data.proxyServer;
            document.getElementById('proxyPort').value = configData.data.proxyPort;
            document.getElementById('sni').value = configData.data.sni;

            handleModeChange();
            handleHttpProxyChange();
        } else {
            throw new Error(configData.message);
        }
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error
        });
    }
}

function showSection(sectionId) {
    document.querySelectorAll('.section').forEach(section => {
        section.classList.add('hidden');
    });
    document.getElementById(sectionId).classList.remove('hidden');
}

function fetchLog() {
    axios.post('api.php', {
        action: 'log'
    }).then(function (response) {
        if (response.data.status === 'OK') {
            const logElement = document.getElementById('getlog');
            logElement.value = response.data.data.replace(/\n/g, '\n');
            logElement.scrollTop = logElement.scrollHeight;  // Auto-scroll ke bawah
        }
    });
}

async function CleanLog() {
    document.getElementById('btnClean').innerHTML = '<i class="fa fa-spinner fa-spin mr-2"></i>Cleaning';
    document.getElementById('btnClean').disabled = true;
    await axios.post('api.php', {
        action: "cleanLog"
    }).then((res) => {
        document.getElementById('btnClean').innerHTML = 'Clear Logs';
        document.getElementById('btnClean').disabled = false;
    });
    document.getElementById('btnClean').innerHTML = 'Clear Logs';
    document.getElementById('btnClean').disabled = false;
}

function getDashboard() {
    return new Promise((resolve, reject) => {
        axios.get('http://ip-api.com/json?fields=query,country,isp')
            .then((res) => {
                document.getElementById('wanOverwiew').innerHTML = ` IP: ${res.data.query}`;
                document.getElementById('locationOverwiew').innerHTML = ` Location: (${res.data.country})`;
                document.getElementById('ispOverwiew').innerHTML = ` ISP: ${res.data.isp}`;
                resolve(res);
            })
            .catch((error) => {
                document.getElementById('wanOverwiew').innerHTML = ` IP: 127.0.0.1`;
                document.getElementById('locationOverwiew').innerHTML = ` Location: (none)`;
                document.getElementById('ispOverwiew').innerHTML = ` ISP: none`;
                reject(error);
            });
    });
}

document.addEventListener('DOMContentLoaded', function () {
    loadStatus();
    handleModeChange();
    handleHttpProxyChange();
    loadConfigData();
    fetchLog();
    setInterval(fetchLog, 1000);
    setInterval(getDashboard, 2000);
});
