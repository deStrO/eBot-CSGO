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
                            archive.append(fs.createReadStream(demo), {name: files.file.name});
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
            if (request.method == 'POST') {
                var body = '';
                request.on('data', function(data) {
                    body += data;
                });
                request.on('end', function() {
                    var data = {};
                    try {
                        data = JSON.parse(body);
                        if (data.message === "ping") {
                            return;
                        }
                    } catch (e) {
                    }
                    if (request.url == "/alive") {
                        io.sockets.in('alive').emit('aliveHandler', {data: body});
                    } else if (request.url == "/rcon") {
                        io.sockets.in('rcon-' + data.id).emit('rconHandler', body);
                    } else if (request.url == "/logger") {
                        io.sockets.in('logger-' + data.id).emit('loggerHandler', body);
                        io.sockets.in('loggersGlobal').emit('loggerGlobalHandler', body);
                    } else if (request.url == "/match") {
                        io.sockets.in('matchs').emit('matchsHandler', body);
                    } else if (request.url == "livemap") {
                        io.sockets.in('livemap-' + data.id).emit('livemapHandler', body);
                    } else {
                        message = JSON.parse(body);
                    }
                });
            }

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

var io = require('socket.io').listen(server);

io.set('log level', 0);

io.sockets.on('connection', function(socket) {
    socket.taggedLogger = false;
    socket.on('identify', function(data) {
        if (data.type === "alive") {
            socket.join("alive");
            var dgram = new Buffer("__aliveCheck__");
            clientUDP.send(dgram, 0, dgram.length, udp_port, udp_ip);
        } else if (data.type === "logger") {
            if (data.match_id) {
                socket.join("logger-" + data.match_id);
            } else {
                socket.join("loggersGlobal");
            }

            socket.join("loggers");
            socket.taggedLogger = true;

            // Send an UDP packet to enable logger forwading
            var dgram = new Buffer("__true__");
            clientUDP.send(dgram, 0, dgram.length, udp_port, udp_ip);
        } else if ((data.type === "rcon") && data.match_id) {
            socket.join("rcon-" + data.match_id);
        } else if ((data.type === "livemap") && data.match_id) {
            socket.join("livemap-" + data.match_id);
        } else if (data.type === "matchs") {
            socket.join("matchs");
        } else if (data.type === "relay") {
            socket.join("relay");
        }
    });

    socket.on('disconnect', function(data) {
        if (socket.taggedLogger) {
            if (io.sockets.clients('loggers').length == 1) {
                var dgram = new Buffer("__false__");
                clientUDP.send(dgram, 0, dgram.length, udp_port, udp_ip);
            }
        }
    });

    socket.on('rconSend', function(data) {
        var dgram = new Buffer(data);
        clientUDP.send(dgram, 0, dgram.length, udp_port, udp_ip);
    });

    socket.on('matchCommandSend', function(data) {
        var dgram = new Buffer(data);
        clientUDP.send(dgram, 0, dgram.length, udp_port, udp_ip);
    });
});

var dgram = require('dgram');
var udpServer = dgram.createSocket('udp4');

udpServer.on('listening', function() {
    var address = udpServer.address();
    console.log('UDP Server listening on ' + address.address + ":" + address.port);
});

udpServer.on('message', function(message, remote) {
    var messageObject = JSON.parse(message);
    var body = messageObject.data;
    var data = {};
    try {
        var data = JSON.parse(body);
        if (data.message == "ping") {
            return;
        }
    } catch (e) {

    }
    if (messageObject.scope == "alive") {
        io.sockets.in('alive').emit('aliveHandler', {data: body});
        io.sockets.in('relay').emit('relay', {channel: 'alive', 'method': 'aliveHandler', content: body});
    } else if (messageObject.scope == "rcon") {
        io.sockets.in('rcon-' + data.id).emit('rconHandler', body);
    } else if (messageObject.scope == "logger") {
        io.sockets.in('logger-' + data.id).emit('loggerHandler', body);
        io.sockets.in('loggersGlobal').emit('loggerGlobalHandler', body);
    } else if (messageObject.scope == "match") {
        io.sockets.in('matchs').emit('matchsHandler', body);
        io.sockets.in('relay').emit('relay', {channel: 'matchs', 'method': 'matchsHandler', content: body});
    } else if (messageObject.scope == "livemap") {
        io.sockets.in('livemap-' + data.id).emit('livemapHandler', body);
        io.sockets.in('relay').emit('relay', {channel: 'livemap-' + data.id, 'method': 'livemapHandler', content: body});
    }
});

udpServer.bind(parseInt(udp_port) + 1, udp_ip);