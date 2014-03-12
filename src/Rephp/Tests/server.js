var WebSocketServer = require('ws').Server;


var wss = new WebSocketServer({port: 8081, host: '172.16.10.133'})
	clients = {},
	success = 0;

wss.on('connection', function(ws) {
	ws.cid = ++success;
	clients[ws.cid] = ws;
	ws.on('message', function(message) {
		for(var cid in clients){
			if(cid == this.cid) continue;
			clients[cid].send(message);
		}
		console.log('Clients: ' + success);
	    });
});
