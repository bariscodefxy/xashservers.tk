<?php

echo(
'$(function(){

	function loadServers() {
		$.ajax({
			url: "'.$sub.'/api/get_servers.php",
			data: [],
			dataType: "JSON",
			type: "POST",
			success: function(data){
				const container = $(\'.servers\')
				let html = `<table class="table">
							  <thead>
							    <tr>
							      <th scope="col">#</th>
							      <th scope="col">IP:PORT</th>
							      <th scope="col">Name</th>
							      <th scope="col">Map</th>
							      <th scope="col">Players</th>
								  <th scope="col">OS</th>
							      <th scope="col">Update</th>
							    </tr>
							  </thead>
							  <tbody>`;
				$(container).html(\'\');
				let i = 1;
				data.forEach(server => {
					html += `<tr><th scope="row">${i}</th><td>${server.ip}:${server.port}</td><td>${server.name}</td><td>${server.map}</td><td>${server.activeplayers}/${server.maxplayers}</td><td>${server.os}</td><td>${server.lastregistry}</td></tr>`;
					i += 1;
				});
				html += `</tbody>
						</table>`;
				$(container).append(html);
			}
		});
	}

	setInterval(function(){
		loadServers();
	}, 35000);
	loadServers();

})
');