var WebSocket = require('ws'),
	conns = [],
	successConns = 0,
	i = 0;


while(i++ < 50000){
	var ws = new WebSocket('ws://172.16.10.133:8081');
	//var ws = new WebSocket('ws://localhost:8081');
	ws.on('open', function() {
		successConns++;

		if(successConns % 100 == 0){
			console.log(successConns);
		}

		if(successConns % 1000 == 0){
			this.on('message', function(msg){
				console.log(msg);
			})
		}
	})
	.on('error', function(err) {
		console.log(err);
		return;
	})
	.on('close', function(e){
		console.log('closed!');
	} )

	conns.push(ws);
}
console.log('Done');
