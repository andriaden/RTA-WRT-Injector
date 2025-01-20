const MODE_CONFIG = {
    SSH: "1",
    SSH_SSL: "2",
    SSH_WS_CDN: "3"
};

const ELEMENT_IDS = {
    mode: 'mode',
    sniField: 'sniField',
    payloadField: 'payloadField',
    enableHttpProxyField: 'enableHttpProxyField',
    proxyFields: 'proxyFields',
    saveButton: 'saveButton',
    startButton: 'startButton',
    stopButton: 'stopButton',
    statusOverview: 'statusOverwiew',
    getLog: 'getlog',
    btnClean: 'btnClean',
    wanOverview: 'wanOverwiew',
    locationOverview: 'locationOverwiew',
    ispOverview: 'ispOverwiew'
};

let modeconfig;

function toggleVisibility(elementId, shouldShow) {
    const element = document.getElementById(elementId);
    if (!element) return;
    
    element.classList.toggle('hidden', !shouldShow);
}

function handleModeChange() {
    const modeSelect = document.getElementById(ELEMENT_IDS.mode);
    if (!modeSelect) return;

    const mode = modeSelect.value;
    const enableHttpProxy = document.getElementById('enableHttpProxy');

    toggleVisibility(ELEMENT_IDS.sniField, mode !== 'SSH');
    toggleVisibility(ELEMENT_IDS.payloadField, mode !== 'SSH - SSL');
    toggleVisibility(ELEMENT_IDS.enableHttpProxyField, mode === 'SSH');
    toggleVisibility(
        ELEMENT_IDS.proxyFields, 
        mode === 'SSH' && enableHttpProxy && enableHttpProxy.checked
    );

    modeconfig = MODE_CONFIG[mode.replace(/ /g, '_')] || "0";
}

function disableInputs(disable) {
    const inputs = document.querySelectorAll('input, select, textarea');
    const saveButton = document.getElementById(ELEMENT_IDS.saveButton);
    
    if (!saveButton) return;

    inputs.forEach(input => {
        if (!['home', 'log', 'config', 'about'].includes(input.id)) {
            input.disabled = disable;
        }
    });
    saveButton.hidden = disable;
}

async function apiCall(endpoint, data) {
    try {
        const response = await axios.post(endpoint, data);
        return response.data;
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.response?.data?.message || 'An error occurred while communicating with the server.'
        });
        throw error;
    }
}

async function loadStatus() {
    try {
        const { data } = await apiCall('api.php', { action: 'getStatus' });
        const isRunning = data.status === 'running';

        const startButton = document.getElementById(ELEMENT_IDS.startButton);
        const stopButton = document.getElementById(ELEMENT_IDS.stopButton);
        const statusOverview = document.getElementById(ELEMENT_IDS.statusOverview);

        if (startButton) startButton.hidden = isRunning;
        if (stopButton) stopButton.hidden = !isRunning;
        if (statusOverview) {
            statusOverview.innerHTML = `Status: ${isRunning ? 'Connected' : 'Stopped'}`;
        }

        disableInputs(isRunning);
    } catch (error) {
        console.error('Status load error:', error);
    }
}

async function handleStartStop(action) {
    const isStart = action === 'start';
    const button = document.getElementById(isStart ? ELEMENT_IDS.startButton : ELEMENT_IDS.stopButton);
    
    if (!button) return;

    const originalText = button.innerHTML;
    button.innerHTML = isStart 
        ? '<i class="fa fa-spinner fa-spin mr-2"></i>Connecting' 
        : '<i class="fa fa-spinner fa-spin mr-2"></i>Stopping';
    button.disabled = true;

    try {
        const serverHost = document.getElementById('serverHost');
        if (isStart && (!serverHost || !serverHost.value)) {
            throw new Error('Server Host is required.');
        }

        await handleSaveButton('silent');
        const response = await apiCall('api.php', { action: `${action}Tunnel` });

        // Tambahkan penanganan respons dari server
        if (response.status !== 'CONNECTING' && response.status !== 'STOPPED') {
            throw new Error(response.message || 'Failed to change tunnel status');
        }

        // Tunggu sedikit lebih lama untuk memastikan perubahan
        await new Promise(resolve => setTimeout(resolve, 3000));
        await loadStatus();

        // Tampilkan pesan sukses
        Swal.fire({
            icon: 'success',
            title: isStart ? 'Connected' : 'Stopped',
            text: response.message || `Tunnel ${isStart ? 'started' : 'stopped'} successfully`
        });
    } catch (error) {
        Swal.fire ({
            icon: 'error',
            title: 'Error',
            text: error.message || 'Failed to change tunnel status'
        });
        console.error(error);
    } finally {
        button.innerHTML = originalText;
        button.disabled = false;
    }
}

async function handleSaveButton(options) {
    const saveButton = document.getElementById(ELEMENT_IDS.saveButton);
    if (!saveButton) return;

    const originalText = saveButton.innerHTML;
    saveButton.innerHTML = '<i class="fa fa-spinner fa-spin mr-2"></i>Saving';
    saveButton.disabled = true;

    try {
        const configData = {
            tun2socks: document.getElementById('tun2socks').checked,
            memoryCleaner: document.getElementById('memoryCleaner').checked,
            autoReconnect: document.getElementById('autoReconnect').checked,
            pingLoop: document.getElementById('pingLoop').checked,
            mode: document.getElementById(ELEMENT_IDS.mode).value,
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
        };

        const response = await apiCall('api.php', { action: 'saveConfig', data: configData });

        if (options !== 'silent') {
            Swal.fire({
                icon: 'success',
                title: 'Saved',
                text: response.message || 'Configuration saved successfully'
            });
        }
    } catch (error) {
        console.error(error);
    } finally {
        saveButton.innerHTML = originalText;
        saveButton.disabled = false;
    }
}

// Tambahkan event listeners
document.addEventListener('DOMContentLoaded', function () {
    const modeSelect = document.getElementById(ELEMENT_IDS.mode);
    const enableHttpProxy = document.getElementById('enableHttpProxy');

    if (modeSelect) {
        modeSelect.addEventListener('change', handleModeChange);
    }

    if (enableHttpProxy) {
        enableHttpProxy.addEventListener('change', handleModeChange);
    }

    loadStatus();
    handleModeChange();
    
    // Gunakan setTimeout untuk mengurangi beban server
    const statusInterval = setInterval(loadStatus, 5000);
    
    // Clear interval saat halaman ditutup
    window.addEventListener('beforeunload', () => clearInterval(statusInterval));
});