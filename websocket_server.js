var WebSocketServer = require('websocket').server;
var http = require('http');
var formidable = require('formidable');
var archiver = require('archiver');
var fs = require('fs');
var dgram = require('dgram');
var clientUDP = dgram.createSocket("udp4");

var udp_ip = process.argv[2];
var udp_port = process.argv[3];

var DEMO_PATH = __dirname + "/demos/";

String.prototype.endsWith = function(suffix) {
    return this.indexOf(suffix, this.length - suffix.length) !== -1;
};

var server = http.createServer(function(request, response) {
    switch (request.url) {

        case '/upload':
            var form = new formidable.IncomingForm({uploadDir: DEMO_PATH});

            form.parse(request, function(err, fields, files) {
                if (files.file) {
                    if (files.file.name.endsWith(".dem")) {
                        if (files.file.type == "application/octet-stream") {
                            console.log("Recieved file");

                            if (fs.existsSync(DEMO_PATH + files.file.name + ".zip"))
                                fs.unlinkSync(DEMO_PATH + files.file.name + ".zip");
                            if (fs.existsSync(files.file.path))
                                fs.renameSync(files.file.path, DEMO_PATH + files.file.name);

                            var output = fs.createWriteStream(DEMO_PATH + files.file.name + ".zip");
                            var archive = archiver('zip');
                            archive.pipe(output);

                            var demo = DEMO_PATH + files.file.name;
                            archive.append(fs.createReadStream(demo), { name: files.file.name });
                            archive.finalize();

                            if (fs.existsSync(DEMO_PATH + files.file.name) && fs.existsSync(DEMO_PATH + files.file.name + ".zip"))
                                fs.unlinkSync(DEMO_PATH + files.file.name);
                        }
                    } else {
                        console.error("bad file uploaded " + files.file);
                    }
                }
                response.writeHead(200, {'content-type': 'text/plain'});
                response.end();
            });

            break;
        default:
            response.writeHead(404);
            response.end();
            break;
    }
});

fs.exists(DEMO_PATH, function(exists) {
    if (!exists) {
        fs.mkdir(DEMO_PATH);
    }
});

server.listen(udp_port, function() {
    console.log((new Date()) + ' Server is listening on port ' + udp_port);
});

wsServer = new WebSocketServer({
    httpServer: server,
    autoAcceptConnections: false
});

function originIsAllowed(origin) {
    // put logic here to detect whether the specified origin is allowed.
    return true;
}

if (!Array.prototype.indexOf) {
    Array.prototype.indexOf = function(obj, fromIndex) {
        if (fromIndex == null) {
            fromIndex = 0;
        } else if (fromIndex < 0) {
            fromIndex = Math.max(0, this.length + fromIndex);
        }
        for (var i = fromIndex, j = this.length; i < j; i++) {
            if (this[i] === obj)
                return i;
        }
        return -1;
    };
}

var clients = new Array();
clients["alive"] = new Array();
clients["logger"] = new Array();
clients["livemap"] = new Array();
clients["rcon"] = new Array();
clients["match"] = new Array();
clients["chat"] = new Array();
var chatlog = new Array();
chatlog.push("chatlog");

wsServer.on('request', function(request) {
    if (!originIsAllowed(request.origin)) {
        // Make sure we only accept requests from an allowed origin
        request.reject();
        console.log((new Date()) + ' Connection from origin ' + request.origin + ' rejected.');
        return;
    }

    var connection = request.accept(null, request.origin);
    var mode = request.resourceURL.path;
    console.log((new Date()) + ' \[' + request.origin + '\] Connection accepted for ' + request.resourceURL.path + ' (' + request.remoteAddress + ')');

    var clientIndex = null;

    if (mode == "/alive") {
        connection.alive = new Array();
        clients["alive"].push(connection);
        if (request.remoteAddress != udp_ip) {
            var dgram = new Buffer("__aliveCheck__");
            clientUDP.send(dgram, 0, dgram.length, udp_port, udp_ip);
        }
    } else if (mode == "/logger") {
        connection.logger = new Array();
        clients["logger"].push(connection);
        if (request.remoteAddress != udp_ip) {
            var dgram = new Buffer("__true__");
            clientUDP.send(dgram, 0, dgram.length, udp_port, udp_ip);
        }
    } else if (mode == "/rcon") {
        connection.rcon = new Array();
        clients["rcon"].push(connection);
    } else if (mode == "/match") {
        connection.match = new Array();
        clients["match"].push(connection);
    } else if (mode == "/livemap") {
        connection.livemap = new Array();
        clients["livemap"].push(connection);
    } else if (mode == "/chat") {
        connection.chat = new Array();
        clients["chat"].push(connection);
        connection.send(JSON.stringify(chatlog));
        for (var c in clients["chat"]) {
            clients["chat"][c].send(clients["chat"].length);
        }
    } else {
        console.log("unknow mode " + mode);
    }

    connection.on('message', function(message) {
        if (message.type === 'binary') {
            return;
        }

        var data = {};
        try {
            data = JSON.parse(message.utf8Data);
        } catch (e) {
        }

        if (data.message == "ping") {
            return;
        } else if (mode == "/alive") {
            for (var c in clients["alive"]) {
                if (clients["alive"][c].remoteAddress != udp_ip) {
                    clients["alive"][c].send(message.utf8Data);
                }
            }
        } else if (mode == "/rcon") {
            clientIndex = clients['rcon'].indexOf(connection, null);
            if (request.remoteAddress != udp_ip) {
                var regex = /registerMatch_(\d+)/;
                if (message.utf8Data.match(regex)) {
                    data = message.utf8Data.match(regex);
                    clients['rcon'][clientIndex].rcon.push(data[1]);
                } else {
                    var dgram = new Buffer(message.utf8Data);
                    clientUDP.send(dgram, 0, dgram.length, udp_port, udp_ip);
                }
                sentByServer = false;
            } else {
                for (var c in clients["rcon"]) {
                    if (clients["rcon"][c].remoteAddress == udp_ip) {
                        continue;
                    } else if (c == clientIndex || clients['rcon'][c].rcon.indexOf(data.id, null) < 0) {
                        continue;
                    }
                    clients["rcon"][c].send(message.utf8Data);
                }
            }
        } else if (mode == "/logger") {
            clientIndex = clients['logger'].indexOf(connection, null);
            if (request.remoteAddress != udp_ip) {
                var regex = /registerMatch_(\d+)/;
                if (message.utf8Data.match(regex)) {
                    data = message.utf8Data.match(regex);
                    clients['logger'][clientIndex].logger.push(data[1]);
                }
                return;
            }
            for (var c in clients["logger"]) {
                if (clients['logger'][c].logger.indexOf(data.id, null) >= 0) {
                    clients["logger"][c].send(message.utf8Data);
                }
            }
        } else if (mode == "/match") {
            clientIndex = clients['match'].indexOf(connection, null);
            if (request.remoteAddress != udp_ip) {
                var dgram = new Buffer(message.utf8Data);
                clientUDP.send(dgram, 0, dgram.length, udp_port, udp_ip);
            }
            for (var c in clients["match"]) {
                if (clientIndex == c) {
                    continue;
                }
                clients["match"][c].send(message.utf8Data);
            }
        } else if (mode == "/chat") {
            chatlog.push(message.utf8Data);
            if (chatlog.length >= 20)
                chatlog.splice(1, 1);
            for (var c in clients["chat"]) {
                clients["chat"][c].send(message.utf8Data);
                clients["chat"][c].send(clients["chat"].length);
            }
        }
    });

    connection.on('close', function(reasonCode, description) {
        if (mode == "/alive") {
            clientIndex = clients['alive'].indexOf(connection, null);
            clients['alive'].splice(clientIndex, 1);
        } else if (mode == "/match") {
            clientIndex = clients['match'].indexOf(connection, null);
            clients['match'].splice(clientIndex, 1);
        } else if (mode == "/rcon") {
            clientIndex = clients['rcon'].indexOf(connection, null);
            clients['rcon'].splice(clientIndex, 1);
        } else if (mode == "/logger") {
            clientIndex = clients['logger'].indexOf(connection, null);
            clients['logger'].splice(clientIndex, 1);
            if (clients['logger'].length == 1) {
                var dgram = new Buffer("__false__");
                clientUDP.send(dgram, 0, dgram.length, udp_port, udp_ip);
            }
        } else if (mode == "/livemap") {
            clientIndex = clients['livemap'].indexOf(connection, null);
            clients['livemap'].splice(clientIndex, 1);
        } else if (mode == "/chat") {
            clientIndex = clients['chat'].indexOf(connection, null);
            clients['chat'].splice(clientIndex, 1);
        }
        console.log((new Date()) + ' \[' + request.origin + '\] Peer ' + connection.remoteAddress + ' disconnected. (' + mode + ') (#' + clientIndex + ')');
    });
});
