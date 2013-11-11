var http = require('http');

var udp_ip = process.argv[2];
var udp_port = process.argv[3];
var relay_ip = process.argv[4];
var relay_port = process.argv[5];

var server = http.createServer(function(request, response) {
});

server.listen(udp_port, function() {
    console.log((new Date()) + ' Server is listening on port ' + udp_port);
});

var io = require('socket.io').listen(server);

io.set('log level', 0);

io.sockets.on('connection', function(socket) {
    socket.on('identify', function(data) {
        if (data.type === "alive") {
            socket.join("alive");
        } else if ((data.type === "livemap") && data.match_id) {
            socket.join("livemap-" + data.match_id);
        } else if (data.type === "matchs") {
            socket.join("matchs");
        }
    });
});

var socketClient = require('socket.io-client').connect('http://' + relay_ip + ':' + relay_port);
socketClient.emit("identify", { type: "relay" });
socketClient.on('relay', function(datas) {
    io.sockets.in(datas.channel).emit(datas.method, datas.content);
});