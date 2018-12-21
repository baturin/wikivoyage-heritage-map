<!DOCTYPE html>
<html>
<!-- 
Artmap - Version 2014-09-07

Author:
  http://de.wikivoyage.org/wiki/User:Mey2008
Contributors:
  http://it.wikivoyage.org/wiki/Utente:Andyrom75
License: 
  Affero GPL v3 or later http://www.gnu.org/licenses/agpl-3.0.html
Recent changes:
  2014-09-07: Images path
  2014-08-24: + thumbnails
  2014-07-30: IE7 / Firefox fix: tab
  2014-04-15: Https tiles, if possible
  2014-04-07: Zoom display grey color
  2014-03-01: Script cleaned up
  2014-02-26: Chinese articles
ToDo:
  2014-08-17: improve speed
  2014-08-17: enlargable images
-->
<head>
  <title>Wikivoyage - geocoded articles</title>

  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">

  <link rel="icon" href="./lib/images/favicon.png" type="image/png" />
  <link rel="stylesheet" href="./lib/leaflet.css" />  
  <link rel="stylesheet" href="./lib/poimap.css" />
  <link rel="stylesheet" href="./lib/MarkerCluster.css" />
  <link rel="stylesheet" href="./lib/MarkerCluster.Default.css" />
  <link rel="stylesheet" href="./lib/Control.OSMGeocoder.css" />

  <script type="text/javascript" src="./lib/leaflet.js"></script>
  <script type="text/javascript" src="./lib/buttons-artmap.js"></script>
  <script type="text/javascript" src="./lib/zoomdisplay.js"></script>
  <script type="text/javascript" src="./lib/leaflet.markercluster.js"></script>
  <script type="text/javascript" src="./lib/Control.OSMGeocoder.js"></script>
  <script type="text/javascript" src="./data/<?php echo $_GET["lang"] ?: "it"; ?>-articles.js"></script>
  <script type="text/javascript" src="./data/<?php echo $_GET["lang"] ?: "it"; ?>-images.js"></script>
</head>

<body>
  <div id="map">
  <script type="text/javascript">

  function onAll() {
    map.setView([40,10],2);
    return false;
  }

  var nr = (addressPoints.length);
  if (navigator.appVersion.substring(0, 1) == 4){
    nr = nr - 1; // fix for old Explorers
  };
  var lang = "<?php echo ($_GET["lang"]) ?: "it"; ?>";
  document.title = "Wikivoyage - " + nr + " geocoded articles";

  var map = L.map('map', {zoomControl: false, minZoom:2, maxZoom: 18}).setView([40,10],2);
  var wikivoyageAttribution = ' POI (only geocoded articles are displayed) © <a href="http://' + lang + '.wikivoyage.org/wiki/">Wikivoyage</a> by <a href="http://creativecommons.org/licenses/by-sa/3.0/deed.' + lang + '">CC-BY-SA</a>';

// Base layer "Mapquestopen" https
  var mapquestopenUrl = 'https://{s}.mqcdn.com/tiles/1.0.0/map/{z}/{x}/{y}.png', subDomains = ['otile1-s','otile2-s','otile3-s','otile4-s'];
  var mapquestopenAttrib = 'Map Data © <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors, Tiles by <a href="http://open.mapquest.co.uk">MapQuest</a>' + wikivoyageAttribution;
  var mapquestopen = new L.TileLayer(mapquestopenUrl, {minZoom: 2, maxZoom: 18, attribution: mapquestopenAttrib, subdomains: subDomains});
  map.addLayer(mapquestopen);

// Layer "markers"
  var markers = new L.MarkerClusterGroup();
  var a = addressPoints[0]; // coordinates + article name
  var b = images[0]; // image name
  var marker = new L.Marker();
  var tp = '//upload.wikimedia.org/wikipedia/commons/thumb/'; // thumbnail path
  var ap = '//' + lang + '.wikivoyage.org/wiki/'; // WV article path
	for (var i = 0; i < nr; i++) {
    a = addressPoints[i];
    b = images[i];
    marker = new L.Marker(new L.LatLng(a[0], a[1]), {title: a[2]}).bindPopup('<img src="' + tp + b + '/120px-' + b.substring(5) + '"> <a href="' + ap + a[2] + '">' + a[2] + '</a>',{minWidth:120, maxWidth:120}); 
    markers.addLayer(marker);
  }
  map.addLayer(markers);

// Controls
  var osmGeocoder = new L.Control.OSMGeocoder({collapsed: false});
  map.addControl(osmGeocoder);
  map.addControl(new L.Control.Layers({'Mapquest Open': mapquestopen}, {'WV articles': markers}));
  map.addControl(new L.Control.Scale());
  map.addControl(new L.Control.Buttons());

  </script>

  <div id="logo">
    <img src="./lib/images/logo.png" alt= "Logo" width="64" height="64">
  </div>
</div> <!--map-->
</body>
</html>
