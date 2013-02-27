var WebSocketServer = require('websocket').server;
var http = require('http');

var dgram = require('dgram');
var clientUDP = dgram.createSocket("udp4");

var udp_ip = process.argv[2];
var udp_port = process.argv[3];

var server = http.createServer(function(request, response) {
    console.log((new Date()) + ' Received request for ' + request.url);
    response.writeHead(404);
    response.end();
});

server.listen(udp_port, function() {
    console.log((new Date()) + ' Server is listening on port '+udp_port);
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
    Array.prototype.indexOf = function (obj, fromIndex) {
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

wsServer.on('request', function(request) {
    console.log("New connection");
    if (!originIsAllowed(request.origin)) {
        // Make sure we only accept requests from an allowed origin
        request.reject();
        console.log((new Date()) + ' Connection from origin ' + request.origin + ' rejected.');
        return;
    }
    
    var connection = request.accept(null, request.origin);
    var mode = request.resourceURL.path;
    console.log((new Date()) + ' Connection accepted for '+request.resourceURL.path+ ' ('+request.remoteAddress+')');

    var index = 0;

    if (mode == "/alive") {
        connection.alive = new Array();
        index = clients["alive"].push(connection) - 1;
        if (request.remoteAddress != udp_ip) {
            var dgram = new Buffer("__aliveCheck__");
            clientUDP.send(dgram, 0, dgram.length, udp_port, udp_ip);
        }
    } else if (mode == "/logger") {
        connection.logger = new Array();
        index = clients["logger"].push(connection) - 1;
        if (request.remoteAddress != udp_ip) {
            var dgram = new Buffer("__true__");
            clientUDP.send(dgram, 0, dgram.length, udp_port, udp_ip);
        }
    } else if (mode == "/rcon") {
        connection.rcon = new Array();
        index = clients["rcon"].push(connection) - 1;
    } else if (mode == "/match") {
        connection.match = new Array();
        index = clients["match"].push(connection) - 1;
    } else if (mode == "/livemap") {
        connection.livemap = new Array();
        index = clients["livemap"].push(connection) - 1;
    } else {
        console.log("unknow mode "+mode);
    }

    connection.on('message', function(message) {
        if (message.type === 'binary') {
            return;
        }
        
        var data = {};
        try {
            data = JSON.parse(message.utf8Data);
        } catch (e) { }

        if (data.message == "ping") {
            return;
        }

        if (mode == "/alive") {
            for (var c in clients["alive"]) {
                if (clients["alive"][c].remoteAddress != udp_ip) {
                    clients["alive"][c].send(message.utf8Data);
                }
            }
        }
        if (mode == "/rcon") {
            var sentByServer = true;

            if (request.remoteAddress != udp_ip) {
                var regex = /registerMatch_(\d+)/;
                if (message.utf8Data.match(regex)) {
                    data = message.utf8Data.match(regex);
                    clients['rcon'][index].rcon.push(data[1]);
                } else {
                    var dgram = new Buffer(message.utf8Data);
                    clientUDP.send(dgram, 0, dgram.length, udp_port, udp_ip);
                }
                sentByServer = false;
            }

            if (sentByServer) {
                for (var c in clients["rcon"]) {
                    if (clients["rcon"][c].remoteAddress == udp_ip) {
                        continue;
                    }
                    
                    if (c == index || clients['rcon'][c].rcon.indexOf(data.id, null) < 0) {
                        continue;
                    }
                    clients["rcon"][c].send(message.utf8Data);
                }
            }
        }

        if (mode == "/logger") {
            if (request.remoteAddress != udp_ip) {
                var regex = /registerMatch_(\d+)/;
                if (message.utf8Data.match(regex)) {
                    data = message.utf8Data.match(regex);
                    clients['logger'][index].logger.push(data[1]);
                }
                return;
            }

            for (var c in clients["logger"]) {
                if (clients['logger'][c].logger.indexOf(data.id, null) >= 0) {
                    clients["logger"][c].send(message.utf8Data);
                }
            }
        }

        if (mode == "/match") {
            if (request.remoteAddress != udp_ip) {
                var dgram = new Buffer(message.utf8Data);
                clientUDP.send(dgram, 0, dgram.length, udp_port, udp_ip);
            }

            for (var c in clients["match"]) {
                if (index == c) {
                    continue;
                }
                clients["match"][c].send(message.utf8Data);
            }
        }
    });

    connection.on('close', function(reasonCode, description) {
        if (mode == "/alive") {
            clients['alive'].splice(index, 1);
        }

        if (mode == "/match") {
            clients['match'].splice(index, 1);
        }

        if (mode == "/rcon") {
            clients['rcon'].splice(index, 1);
        }

        if (mode == "/logger") {
            clients['logger'].splice(index, 1);
            if (clients['logger'].length == 0) {
                var dgram = new Buffer("__false__");
                clientUDP.send(dgram, 0, dgram.length, udp_port, udp_ip);
            }
        }

        if (mode == "/livemap") {
            clients['livemap'].splice(index, 1);
        }
        console.log((new Date()) + ' Peer ' + connection.remoteAddress + ' disconnected. ('+mode+')');
    });
});