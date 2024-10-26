<?php
include("config.inc.php");
?>

<!doctype html>
<html lang="en">
<head>
    <?php
        $title = "Home";
        include("header.php");
        exec('chmod -R 755 /www/rtawrt-injector/* && chmod -R 755 /usr/share/rtawrt-injector/*');
    ?>
</head>
<body>
    <div class="container">
        <h2>RTA-WRT INJECTOR</h2>

        <div class="btn-group mb-3">
            <a href="index.php" class="btn btn-custom" role="button">Home</a>
            <a href="log.php" class="btn btn-custom" role="button">Log</a>
            <a href="config.php" class="btn btn-custom" role="button">Config</a>
            <a href="about.php" class="btn btn-custom" role="button">About</a>
        </div>
        
        <div class="form-group">
            <label for="account">Account SSH:</label>
            <input type="text" class="form-control" placeholder="ip:port@user:pass" value="<?php if ($account) echo ($account); ?>" id="account" required>
        </div>

        <div class="form-group" id="opt-payload">
            <label for="payload">Payload:</label>
            <textarea style="text-align:left" class="form-control" rows="5" placeholder="GET http://server.com/ HTTP/1.1[crlf][crlf]CONNECT [host_port] HTTP/1.1[crlf]Connection: keep-alive[crlf][crlf]" id="payload" required><?php if ($payload2) echo ($payload2); ?></textarea>
        </div>

        <div class="form-group" id="opt-sni-ssl">
            <label for="sni">Server Name Indication (SNI):</label>
            <input type="text" class="form-control" placeholder="bug.com" value="<?php if ($sni2) echo ($sni2); ?>" id="sni">
        </div>

        <div class="form-group" id="opt-remote-proxy">
            <label for="proxy">Remote Proxy:</label>
            <input type="text" class="form-control" placeholder="ip:port" value="<?php if ($proxy2) echo ($proxy2); ?>" id="proxy">
        </div>

        <!-- Tunnel Options Section with Radio Buttons -->
        <div class="form-group">
            <label for="tunnel-options">Tunnel Options:</label>
            <div id="tunnel-options">
                <div class="row">
                    <div class="col-6">
                        <div class="form-check">
                            <input type="radio" class="form-check-input" id="opt-http-payload" name="tunnelOption" value ="mode-http" onclick="mode(this.value)" <?php if (($connection_mode) == "1") echo "checked"; ?>>
                            <label class="form-check-label" for="opt-http-payload">HTTP-Proxy + Payload</label>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-check">
                            <input type="radio" class="form-check-input" id="opt-ssl-direct" name="tunnelOption" value="mode-ssl" onclick="mode(this.value)" <?php if (($connection_mode) == "2") echo "checked"; ?>>
                            <label class="form-check-label" for="opt-ssl-direct">SSL/TLS Direct</label>
                        </div>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-6">
                        <div class="form-check">
                            <input type="radio" class="form-check-input" id="opt-direct-payload" name="tunnelOption" value="mode-direct" onclick="mode(this.value)" <?php if (($connection_mode) == "0") echo "checked"; ?>>
                            <label class="form-check-label" for="opt-direct-payload">SSH Direct + Payload</label>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-check">
                            <input type="radio" class="form-check-input" id="opt-ssl-payload" name="tunnelOption" value="mode-ws-ssl" onclick="mode(this.value)" <?php if (($connection_mode) == "3") echo "checked"; ?>>
                            <label class="form-check-label" for="opt-ssl-payload">SSL/TLS + Payload</label>
                        </div>
                    </div>
                </div>
            </div>
            <br>
        </div>
        
        <div class="form-group">
            <label>Socks Proxy</label>
            <select class="form-control" id="sock_mode" required>
                <option value="1" <?php if (($sock_mode) == "1") echo "selected"; ?>>Badvpn-Tun2socks</option>
                <option value="2" <?php if (($sock_mode) == "2") echo "selected"; ?>>Transparent Proxy</option>
            </select>
            <br>
            <label>Badvpn UDPGW</label>
            <input type="text" class="form-control" placeholder="Default 7300" value="<?php if ($ssh_udp) echo ($ssh_udp); ?>" id="ssh_udp">
        </div>

        <div class="d-grid gap-2">
        <button type="submit" onclick="saveConfig();" id="saveConfig" class="btn btn-primary btn-block">Save</button>
        <?php if (isset($terhubung) && $terhubung): ?>
            <button type="button" onclick="stop();" id="stop" class="btn btn-danger btn-block">Stop</button>
        <?php else: ?>
            <button type="button" onclick="if(validateInputs()) start();" id="start" class="btn btn-custom btn-block">Start</button>
        <?php endif; ?>
        </div>

        <?php include('footer.php'); ?>
    </div>

    <div class="modal fade" id="errorModal" tabindex="-1" role="dialog" aria-labelledby="errorModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="errorModalLabel">Error</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p id="modalMessage"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <?php include("javascript.php"); ?>
</body>
</html>