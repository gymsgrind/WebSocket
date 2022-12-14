const hostname = 'localhost';
const port = 8090;

let Ws = new WebSocket(`ws://${hostname}:${port}/`);

Ws.onopen = function () {
    console.log('Connection established');
    send({
        action: 'InitUser', 
        username: 'John Doe', 
        password: 'Pa33w0rd'
    });
};

Ws.onmessage = function (event) {
    console.log({
        JsonData: event.data
    });
};

Ws.onclose = function (event) {
    if (event.wasClean) {
        console.log('Connection closed');
    } else {
        console.log('Connection interrupted');
    }
};
Ws.onerror = function (event) {
    console.log('Error: ' + event.message);
};

function send(array) {
    Ws.send(JSON.stringify(array));
}