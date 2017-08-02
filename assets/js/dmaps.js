var map_class = document.querySelectorAll(".dmaps_canvas_style");

for( var i = 0; i < map_class.length; i++ ) {

	var id = map_class[i].dataset.id;
	var lat = parseFloat(map_class[i].dataset.lat);
	var lng = parseFloat(map_class[i].dataset.lng);
	var zoom = parseInt(map_class[i].dataset.zoom);
	var radius = parseInt(map_class[i].dataset.radius);
	var radius_color = map_class[i].dataset.radiusc; 

	var mapCanvas = document.getElementById('dmaps_canvas_' + id);

    var mapOptions = {
      	center: new google.maps.LatLng(lat, lng),
      	zoom: zoom,
     	mapTypeId: google.maps.MapTypeId.ROADMAP
    }

    var map = new google.maps.Map(mapCanvas, mapOptions);

    if( radius ) {

		var marker = new google.maps.Marker({
			position: new google.maps.LatLng(lat, lng),
			map: map
		});

		var circle = new google.maps.Circle({
			map: map,
			radius: radius,
			fillColor: radius_color,					  
			strokeWeight:0
		});

		marker.setMap(null);
		circle.bindTo('center', marker, 'position');

    } else {

    	var marker = new google.maps.Marker({
			position: new google.maps.LatLng(lat, lng),
			map: map
		});

    }

}