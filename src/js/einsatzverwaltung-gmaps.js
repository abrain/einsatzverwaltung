var map;
var marker

function initializeMap(lat, lon, zoom) {
  if(!zoom) {
    zoom = 13;
  }
  var latLon = new google.maps.LatLng(lat, lon);
  var mapOptions = {
    zoom: zoom,
    center: latLon
  };
  map = new google.maps.Map(document.getElementById('map-canvas'), mapOptions);
}

function setAdminMarker( position, locationField ) {
  if (marker && marker.setPosition) {
    // if the marker already exists, move it (set its position)
    marker.setPosition(position);
  } else {
    marker = new google.maps.Marker({
      map: map,
      position: position,
      draggable: true,
      scaleControl:true
    });
  }
  map.setCenter(marker.getPosition());
  document.getElementById(locationField).value=marker.getPosition().toUrlValue();
  marker.addListener("drag", function(){
    document.getElementById(locationField).value=marker.getPosition().toUrlValue();
  });
}

function geocodeAddress( address, locationField ) {
  var geocoder = new google.maps.Geocoder();
  geocoder.geocode({"address": address}, function(results, status) {
    if (status === google.maps.GeocoderStatus.OK) {
      var location = results[0].geometry.location;
      setAdminMarker( location, locationField );
      map.setZoom(16);
    } else {
      alert("Konnte Position nicht ermitteln: " + status);
    }
  });
}

function addMarker( lat, lon , infoContent, showContent ) {
  var latLon = new google.maps.LatLng(lat, lon);
  var newMarker = new google.maps.Marker({
      map: map,
      position: latLon,
  });
  var infowindow = new google.maps.InfoWindow({
    content: infoContent
  });
  newMarker.addListener('click', function() {
    infowindow.open(map, newMarker);
  });
  if(showContent) {
    infowindow.open(map, newMarker);
  }
}
