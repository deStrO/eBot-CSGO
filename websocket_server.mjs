import * as http from 'http';
import * as https from 'https';
import * as fs from 'fs';
import {Server} from "socket.io";
import {createApp, createRouter, readBody, eventHandler, toNodeListener, getRequestURL} from "h3";
import {createClient} from 'redis';
import {parse} from 'ini';

const HOST_IP = process.argv[2];
const HOST_PORT = process.argv[3];
const SSL_ENABLED = process.argv[4] === 'TRUE';
const config = parse(fs.readFileSync('./config/config.ini', 'utf-8'));

(async () => {
    const app = createApp();
    let io = null;

    const redisConfig = {
        username: config.Redis.REDIS_AUTH_USERNAME,
        password: config.Redis.REDIS_AUTH_PASSWORD,
        socket: {
            host: config.Redis.REDIS_HOST,
            port: config.Redis.REDIS_PORT,
        }
    };

    const subscriber = createClient(redisConfig);
    await subscriber.connect();

    const client = createClient(redisConfig);
    await client.connect();

    const router = createRouter()
        .post(
            "/upload",
            eventHandler(() => {

            }),
        )
        .post(
            "/**",
            eventHandler(async (event) => {
                const body = await readBody(event);
                const data = body;
                const requestUrl = getRequestURL(event);
                if (requestUrl.pathname === "/alive") {
                    io.to('alive').emit('aliveHandler', {data: body});
                } else if (requestUrl.pathname === "/rcon") {
                    io.to('rcon-' + data.id).emit('rconHandler', body);
                } else if (requestUrl.pathname === "/logger") {
                    io.to('logger-' + data.id).emit('loggerHandler', body);
                    io.to('loggersGlobal').emit('loggerGlobalHandler', body);
                } else if (requestUrl.pathname === "/match") {
                    io.to('matchs').emit('matchsHandler', body);
                } else if (requestUrl.pathname === "livemap") {
                    io.to('livemap-' + data.id).emit('livemapHandler', body);
                }

                return true;
            }),
        );

    app.use(router);


    let server;
    if (SSL_ENABLED) {
        server = https.createServer({
            cert: fs.readFileSync(process.argv[5] || 'ssl/cert.pem'),
            key: fs.readFileSync(process.argv[6] || 'ssl/key.pem'),
        }, toNodeListener(app));
    } else {
        server = http.createServer(toNodeListener(app));
    }

    io = new Server(server, {
        cors: {
            origin: "*"
        }
    });
    io.on('connection', function (socket) {
        socket.taggedLogger = false;
        socket.on('identify', function (data) {
            if (data.type === "alive") {
                socket.join("alive");
                client.lPush(config.Redis.REDIS_CHANNEL_EBOT_FROM_WS, "__aliveCheck__");
            } else if (data.type === "logger") {
                if (data.match_id) {
                    socket.join("logger-" + data.match_id);
                } else {
                    socket.join("loggersGlobal");
                }

                socket.join("loggers");
                socket.taggedLogger = true;
                client.lPush(config.Redis.REDIS_CHANNEL_EBOT_FROM_WS, "__true__");
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

        socket.on('disconnect', async function (data) {
            if (socket.taggedLogger) {
                if ((await io.in('loggers').fetchSockets()).length === 1) {
                    client.lPush(config.Redis.REDIS_CHANNEL_EBOT_FROM_WS, "__false__");
                }
            }
        });

        socket.on('rconSend', function (data) {
            client.lPush(config.Redis.REDIS_CHANNEL_EBOT_FROM_WS, data);
        });

        socket.on('matchCommandSend', function (data) {
            client.lPush(config.Redis.REDIS_CHANNEL_EBOT_FROM_WS, data);
        });
    });


    server.listen(HOST_PORT, HOST_IP, function () {
        console.log((new Date()) + ' Server is listening on ' + HOST_IP + ':' + HOST_PORT);
    });

    await subscriber.subscribe(config.Redis.REDIS_CHANNEL_EBOT_TO_WS, (message) => {
        const messageObject = JSON.parse(message);
        const body = messageObject.data;
        let data;
        try {
            data = JSON.parse(body);
            if (data.message === "ping") {
                return;
            }
        } catch (e) {

        }

        if (messageObject.scope === "alive") {
            io.to('alive').emit('aliveHandler', {data: body});
            io.to('relay').emit('relay', {channel: 'alive', 'method': 'aliveHandler', content: body});
        } else if (messageObject.scope === "rcon") {
            io.to('rcon-' + data.id).emit('rconHandler', body);
        } else if (messageObject.scope === "logger") {
            if (data && data.id) {
                io.to('logger-' + data.id).emit('loggerHandler', body);
            }
            io.to('loggersGlobal').emit('loggerGlobalHandler', body);
        } else if (messageObject.scope === "match") {
            io.to('matchs').emit('matchsHandler', body);
            io.to('relay').emit('relay', {channel: 'matchs', 'method': 'matchsHandler', content: body});
        } else if (messageObject.scope === "livemap") {
            io.to('livemap-' + data.id).emit('livemapHandler', body);
            io.to('relay').emit('relay', {
                channel: 'livemap-' + data.id,
                'method': 'livemapHandler',
                content: body
            });
        }
    });
})()
