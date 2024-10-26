const { makeArray } = require("jquery");

var modeconfig;

$(document).ready(function() {
    // Initialize mode based on initially checked radio button in tunnel-options
    $('#tunnel-options input[type="radio"]:checked').each(function() {
        mode(this.value);
    });

    // Apply mode function whenever a radio button is changed
    $('#tunnel-options input[type="radio"]').change(function() {
        mode(this.value);
    });

    // Set up AJAX to retrieve log data every second
    setInterval(function() {
        $.ajax({
            url: 'logs-2.txt',
            type: 'GET',
            cache: false,
            success: function (response) {
                $("#log").val(response);
            }
        });
    }, 1000);
});

function start() {
    // Validate inputs before starting the process
    if (!validateInputs()) {
        return; // Stop execution if validation fails
    }

    $.ajax({
        url: 'api.php',
        type: 'POST',
        data: {
            action: 'start'
        },
        beforeSend: function () {
            $('#start').html('<span class="spinner-border spinner-border-sm"></span> Start').attr('disabled', true);
            $('#stop').attr('disabled', true);
            $('#autoBootRecon').attr('disabled', true);
            $("#log").val("");
        },
        success: function (response) {
            $('#start').html('Start');
            $('#stop').attr('disabled', false);
            $('#autoBootRecon').attr('disabled', false);
            $("#log").val(response);
        }
    });
}

function stop() {
    $.ajax({
        url: 'api.php',
        type: 'POST',
        data: {
            action: 'stop'
        },
        beforeSend: function () {
            $('#start').attr('disabled', true);
            $('#stop').html('<span class="spinner-border spinner-border-sm"></span> Stop').attr('disabled', true);
            $('#autoBootRecon').attr('disabled', true);
            $("#log").val("");
        },
        success: function (response) {
            $('#start').attr('disabled', false);
            $('#stop').html('Stop').attr('disabled', false);
            $('#autoBootRecon').attr('disabled', false);
            $("#log").val(response);
        }
    });
}

function validateInputs() {
    const account = document.getElementById('account').value;
    const proxy = document.getElementById('proxy').value;
    const accountPattern = /^(.*?):(.*?)@(.*?):(.*?)$/; // Regex for ip:port@user:pass
    const proxyPattern = /^(.*?):(.*?)$/; // Regex for ip:port

    // Check the currently selected mode
    const selectedMode = $('input[name="tunnelOption"]:checked').val(); // Get the value of the checked radio button

    // Validate SSH Account
    if (!accountPattern.test(account)) {
        showModal('Invalid SSH Account format. Please use ip:port@user:pass format.');
        return false;
    }

    // Validate Proxy only if the selected mode is "mode-http"
    if (selectedMode === "mode-http" && !proxyPattern.test(proxy)) {
        showModal('Invalid Proxy format. Please use ip:port format.');
        return false;
    }

    return true;
}

function showModal(message) {
    $('#modalMessage').text(message);
    $('#errorModal').modal('show');
}

function saveConfig() {
    // Validate inputs before saving the configuration
    if (!validateInputs()) {
        return; // Stop execution if validation fails
    }

    var connection_mode = $('input[name="tunnelOption"]:checked').val();
    var sock_mode = $('#sock_mode').val();
    var DEAccount = $('#account').val();
    var [hostPort, userPass] = DEAccount.split('@');
    var [ssh_host, ssh_port] = hostPort.split(':');
    var [ssh_username, ssh_password] = userPass.split(':');
    var ssh_udp = $('#ssh_udp').val();
    
    var DEProxy = $('#proxy').val(); // Get proxy value
    var proxy_ip = '';
    var proxy_port = '';

    // Check if DEProxy has a value
    if (DEProxy) {
        [proxy_ip, proxy_port] = DEProxy.split(':'); // Split into IP and port if DEProxy is not empty
    }

    var sni_server_name = $('#sni').val();
    var payload = $('#payload').val();
    
    // Check if all required SSH fields are filled
    if (ssh_host && ssh_port && ssh_udp && ssh_username && ssh_password) {
        $.ajax({
            url: 'api.php',
            type: 'POST',
            data: {
                action: 'saveConfig',
                connection_mode: connection_mode + '|' + modeconfig,
                sock_mode: sock_mode,
                ssh_host: ssh_host,
                ssh_port: ssh_port,
                ssh_udp: ssh_udp,
                ssh_username: ssh_username,
                ssh_password: ssh_password,
                proxy_ip: proxy_ip,
                proxy_port: proxy_port,
                sni_server_name: sni_server_name,
                payload: payload
            },
            beforeSend: function () {
                $('#saveConfig').attr('disabled', true);
            },
            success: function (response) {
                $('#saveConfig').attr('disabled', false);
                alert(response);
            }
        });
    } else {
        alert("Harap Isi Semua");
    }
}

function autoBootRecon(val) {
    option = val ? 'on' : 'off';
    $.ajax({
        url: 'api.php',
        type: 'POST',
        data: {
            action: 'autoBootRecon',
            option: option
        },
        beforeSend: function () {
            $('#start').attr('disabled', true);
            $('#stop').attr('disabled', true);
            $('#autoBootRecon').attr('disabled', true);
        },
        success: function (response) {
            if (!$("#start").is(":disabled")) $('#start').attr('disabled', false);
            $('#stop').attr('disabled', false);
            $('#autoBootRecon').attr('disabled', false);
            alert(response);
        }
    });
}

function mode(val) {
    // Reset all visibility states at the beginning
    $("#opt-sni-ssl, #opt-remote-proxy, #opt-payload").hide();

    switch (val) {
        case "mode-http":
            $("#opt-remote-proxy, #opt-payload").show();
            modeconfig = "1";
            break;
        case "mode-ssl":
            $("#opt-sni-ssl").show();
            modeconfig = "2";
            break;
        case "mode-direct":
            $("#opt-payload").show();
            modeconfig = "0";
            break;
        case "mode-ws-ssl":
            $("#opt-sni-ssl, #opt-payload").show();
            modeconfig = "3";
            break;
    }
}