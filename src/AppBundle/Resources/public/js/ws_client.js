var ws = WS.connect('ws://127.0.0.1:8080');

ws.on('socket/connect', function(session) {
    console.log('Connected');

    session.subscribe('app/twach', function(uri, payload) {
        console.log('Received message', payload.msg);
    });

    session.publish('app/twach', 'Message');
});

ws.on('socket/disconnect', function(error) {
    console.log('Disconnected');
});