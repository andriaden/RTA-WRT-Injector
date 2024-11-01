<html>
<head>
    <title>RTA-WRT Injector</title>
    <link rel="stylesheet" href="assets/font-awesome/css/font-awesome.min.css">
    <link rel="stylesheet" href="css/tailwind.css">
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen">
    <div class="bg-white p-8 rounded-lg shadow-lg w-full max-w-md h-full overflow-y-auto">
        <h1 class="text-2xl font-bold text-center text-teal-700 mb-6">RTA-WRT INJECTOR</h1>
        <div class="flex justify-center mb-6">
            <button class="bg-teal-700 text-white px-4 py-2 rounded-l" onclick="showSection('home')">Home</button>
            <button class="bg-teal-700 text-white px-4 py-2" onclick="showSection('log')">Log</button>
            <button class="bg-teal-700 text-white px-4 py-2" onclick="showSection('config')">Config</button>
            <button class="bg-teal-700 text-white px-4 py-2 rounded-r" onclick="showSection('about')">About</button>
        </div>
        <div id="home" class="section">
            <h2 class="text-xl font-bold text-teal-700 mb-4">Home</h2>
            <form>
                <div class="mb-4">
                    <label class="block text-gray-700">Status Overview:</label>
                    <div class="flex justify-between mb-2">
                        <span><i id="statusOverwiew" class="fa fa-inbox"></i></span>
                        <span><i id="wanOverwiew" class="fa fa-server"></i></span>
                    </div>
                    <div class="flex justify-between">
                        <span><i id="locationOverwiew" class="fa fa-flag-o"></i></span>
                        <span><i id="ispOverwiew" class="fa fa-globe"></i></span>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700">Options:</label>
                    <div class="flex items-center mb-2">
                        <input type="checkbox" id="tun2socks" class="mr-2">
                        <label for="tun2socks" class="cursor-pointer">Use tun2socks</label>
                    </div>
                    <div class="flex items-center mb-2">
                        <input type="checkbox" id="memoryCleaner" class="mr-2">
                        <label for="memoryCleaner" class="cursor-pointer">Memory cleaner</label>
                    </div>
                    <div class="flex items-center mb-2">
                        <input type="checkbox" id="autoReconnect" class="mr-2">
                        <label for="autoReconnect" class="cursor-pointer">Auto Reconnect</label>
                    </div>
                    <div class="flex items-center mb-2">
                        <input type="checkbox" id="pingLoop" class="mr-2">
                        <label for="pingLoop" class="cursor-pointer">Ping Loop</label>
                    </div>
                </div>
                <div>
                    <button type="button" id="startButton" class="bg-teal-700 text-white w-full px-4 py-2 rounded" onclick="handleStartButton()">Start</button>
                    <button type="button" id="stopButton" class="bg-red-700 text-white w-full px-4 py-2 rounded" hidden onclick="handleStopButton()">Stop</button>
                </div>
            </form>
        </div>
        <div id="log" class="section hidden">
            <h2 class="text-xl font-bold text-teal-700 mb-4">Log</h2>
            <textarea id="getlog" class="w-full px-3 py-2 border rounded mb-4" style="height: 30rem" readonly>Log content goes here...</textarea>
            <button id="btnClean" class="bg-teal-700 text-white w-full px-4 py-2 rounded" onclick="CleanLog()">Clear Logs</button>
        </div>
        <div id="config" class="section hidden">
            <h2 class="text-xl font-bold text-teal-700 mb-4">Config</h2>
            <form>
                <div class="mb-4">
                    <label class="block text-gray-700">Mode:</label>
                    <select id="mode" class="w-full px-3 py-2 border rounded" onchange="handleModeChange()">
                        <option>SSH</option>
                        <option>SSH - SSL</option>
                        <option>SSH - WS - CDN</option>
                    </select>
                </div>
                <div class="mb-4 flex space-x-4">
                    <div class="w-1/2">
                        <label class="block text-gray-700">Server Host:</label>
                        <input type="text" id="serverHost" class="w-full px-3 py-2 border rounded" placeholder="bug/server.com" required>
                    </div>
                    <div class="w-1/2">
                        <label class="block text-gray-700">Server Port:</label>
                        <input type="number" id="serverPort" class="w-full px-3 py-2 border rounded" placeholder="443" required>
                    </div>
                </div>
                <div class="mb-4 flex space-x-4">
                    <div class="w-1/2">
                        <label class="block text-gray-700">Username:</label>
                        <input type="text" id="username" class="w-full px-3 py-2 border rounded" placeholder="rtawrt" required>
                    </div>
                    <div class="w-1/2">
                        <label class="block text-gray-700">Password:</label>
                        <input type="text" id="password" class="w-full px-3 py-2 border rounded" placeholder="rtawrt" required>
                    </div>
                </div>
                <div id="enableHttpProxyField" class="mb-4 flex items-center">
                    <input type="checkbox" id="enableHttpProxy" class="mr-2" onchange="handleHttpProxyChange()">
                    <label for="enableHttpProxy" class="cursor-pointer">Enable HTTP Proxy</label>
                </div>
                <div id="payloadField" class="mb-4">
                    <label class="block text-gray-700">Payload:</label>
                    <textarea id="payload" class="w-full px-3 py-2 border rounded h-24" placeholder="GET http://bug.com/ HTTP/1.1[crlf][crlf]CONNECT [host_port] HTTP/1.1[crlf]Connection: keep-allive[crlf][crlf]" required></textarea>
                </div>
                <div id="proxyFields" class="mb-4 flex space-x-4">
                    <div class="w-1/2">
                        <label class="block text-gray-700">Proxy Server:</label>
                        <input type="text" id="proxyServer" class="w-full px-3 py-2 border rounded" placeholder="127.0.0.1" required>
                    </div>
                    <div class="w-1/2">
                        <label class="block text-gray-700">Proxy Port:</label>
                        <input type="text" id="proxyPort" class="w-full px-3 py-2 border rounded" placeholder="8080" required>
                    </div>
                </div>
                <div id="sniField" class="mb-4">
                    <label class="block text-gray-700">SNI:</label>
                    <input type="text" id="sni" class="w-full px-3 py-2 border rounded" placeholder="bug.com" required>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700">UDPGW:</label>
                    <input type="number" id="udpgw" class="w-full px-3 py-2 border rounded" placeholder="3700" required>
                </div>
                <div class="mb-4">
                    <button type="button" id="saveButton" class="bg-teal-700 text-white w-full px-4 py-2 rounded" onclick="handleSaveButton()">Save</button>
                </div>
            </form>
        </div>
        <div id="about" class="section hidden">
            <h2 class="text-xl font-bold text-teal-700 mb-4">About</h2>
            <p class="text-gray-700">RTA-WRT Injector v1.0.0 Beta</p>
            <p class="text-gray-700">© 2024 RTA-WRT Injector</p>
            <p class="text-gray-700">This application is designed to...</p>
        </div>
    </div>
    <?php include("javascript.php"); ?>
</body>
</html>