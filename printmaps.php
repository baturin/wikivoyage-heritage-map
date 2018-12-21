<!DOCTYPE html>
<html>
<!-- 
  Printmaps - Version 2012-06-14

  Author:
  http://de.wikivoyage.org/wiki/User:Mey2008
     
  License: 
  Affero GPL v3 or later http://www.gnu.org/licenses/agpl-3.0.html 
  
  Recent changes:
  2012-06-14 - new
-->
   
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title> <?php echo $_GET["name"]," — Wikivoyage Map" ?></title>
    <link rel="icon" href="./img/favicon.png" type= "image/png" />
    <link rel="stylesheet" href="./lib/leaflet.css" />
    <link rel="stylesheet" href="./lib/poimap.css" />
    <link rel="stylesheet" href="./lib/MarkerCluster.css" />
  </head>
<body>
<div id="map">
    <script type="text/javascript" src="./lib/leaflet.js"></script>
    <script type="text/javascript" src="./lib/leaflet.markercluster.js"></script>
    <script type="text/javascript" src="./lib/markers.js"></script>
    <script type="text/javascript" src="./lib/gpx.js"></script>
    <script type="text/javascript" src="./data/<?php echo $_GET["lang"]; ?>-articles.js"></script>

<?php

/* //PHP error reporting  *** TEST ***
error_reporting (E_ALL | E_STRICT);
ini_set ('display_errors' , 1);
*/

// reading URL parameters
$lang= $_GET["lang"];
$file= str_replace("\'","'",$_GET["name"]);

// reading article data
$content = file_get_contents("http://" . $lang . ".wikivoyage.org/w/index.php?title=" . $file . "&action=raw");

// strip comments
$content = preg_replace('/<!--(.|\s)*?-->/', '', $content); 

// replace special strings
$content = str_ireplace(array('[[', ']]', '| ', ' |', '= ', ' =', '=====', '===', '&', '{{Marker', '{{Listing', '{{vCard', '?lang=', '@', '{{Poi', '=listing' ), array('', '', '|', '|', '=', '=', 'XXXXX', 'XXX', '%26', '{{listing', '{{listing', '{{listing', 'XxxxxX', 'X', '{{poi', '=' ),  $content);

// replace section 2 headers
$content = preg_replace('/==.*==/', '{{listing|type=**h2**|name=**SECTION**}}', $content); 


// echo $content; // *** TEST ***

// mapmask
preg_match('/{{MapMask\|(.*?)}}/i', $content, $matches);
if (isset($matches[1])) {
  $mask = '[[' . str_replace('|', '],[', $matches[1]) . ']]';
}
else {
  $mask = '[[]]';
}


// read parameters {{listing|
$apart = explode('{{listing', $content);

for($i=1; $i < count($apart); $i++){
  $text = explode('}}', $apart[$i]);
  $part = str_replace('|','&', $text[0]);
  
  $name = $map = $type = $lat = $long = $image = '';
  parse_str($part); 

  $p[$z + $i] = (trim($map)   ?: "0");

// automatic numbering for some versions
  if ( $lang == "el" || $lang == "en" || $lang == "es" || $lang == "fr" || $lang == "he" || $lang == "it" || $lang == "nl" || $lang == "pt" || $lang == "ru" || $lang == "uk" || $lang == "zh") {
    $p[$z + $i] = $nr;
    if(trim($type) == "" && trim($lat) !="") {
      $p[$z + $i] = $nother;
      $nother= $nother + 1;
    }
    elseif (trim($lat) + 0 != 0) {
      $nr = $nr +1;
    }
// Reset for non cont. numbering
   if (trim($type) == "**h2**") {
    $nr= 1;
   }
  }
// -- End of auto numering 

  $c[$z + $i] = (trim($type)  ?: "other");
  $x[$z + $i] = (trim($lat)  + 0 ?: "0");
  $y[$z + $i] = (trim($long) + 0 ?: "0");
  $n[$z + $i] = (trim($name)  ?: "NoName");
  $f[$z + $i] = (str_replace(" ","_",trim($image)) ?: "0/01/no");
  if (substr($f[$z+$i],1,1) != "/") {
    $md5 = md5($f[$z+$i]);
    $f[$z+$i] = substr($md5,0,1) . "/" . substr($md5,0,2) . "/" . $f[$z+$i];
    }
  }
$max = $z + $i - 1;

// checking types
$types = array("**h2**", "black", "blue", "buy", "do", "drink", "eat", "error", "forestgreen", "fun", "go", "gold", "health", "lime", "listing", "maroon", "mediumaquamarine", "other", "red", "see", "silver", "sleep", "steelblue", "view", "vicinity", "health", "around", "city", "diplo");
$i = 1;
while ($i <= $max){
  if (!in_array($c[$i], $types)) {
    $n[$i] = $n[$i] . " | TYPE ERROR: " . $c[$i];
    $c[$i] = "error";
  }
  $i++;
}

$gpxcontent = "";
if ($lang == 'el' || $lang == 'en' || $lang == 'fr' || $lang == 'nl') {
  // Gpx data --> Template:GPX/Articlename
  $gpxcontent = @file_get_contents("http://" . $lang . ".wikivoyage.org/w/index.php?title=Template:GPX/" . $file . "&action=raw");
}
else {
  // Gpx data --> Articlename/Gpx
  $gpxcontent = @file_get_contents("http://" . $lang . ".wikivoyage.org/w/index.php?title=" . $file . "/Gpx&action=raw");
}
if (!$gpxcontent) {
  $gpxcontent = file_get_contents("./lib/empty.gpx");
}
// gpx.js needs seq. file
$fp = fopen("./tracks.gpx", "wb+");
  fwrite($fp, $gpxcontent);
fclose($fp);

// search for fixed color
$fixedcolor = strpos($gpxcontent, 'fixedcolor="yes"');

// echo '<pre>'; print_r($GLOBALS); echo '</pre>'; // *** TEST ***

?>

<noscript> 
 <h2><a href="http://activatejavascript.org/en/">This application needs JavaScript. - See instructions:</a></h2>
</noscript>

<script type='text/javascript'>

// stop for testing // *** TEST ***
// alert("stop for testing"); // *** TEST ***


  // All arrays to js
  var jslat   =  '<?php echo $_GET["lat"] ?: "0";?>';
  if (isNaN(jslat)) { jslat= "0"; alert("ERROR: Lat must be numeric!");}
  jslat =parseFloat(jslat);
  var jslon   =  '<?php echo $_GET["lon"] ?: "0"; ?>';
  if (isNaN(jslon)) { jslon= "0";alert("ERROR: Lon must be numeric!");}
  jslon =parseFloat(jslon);
  var jszoom  =  '<?php echo $_GET["zoom"] ?: "14"; ?>';
  var autozoom = "no";
  if (jszoom == "auto") {autozoom = "yes";}
	if (parseInt(jszoom) < 2 | parseInt(jszoom) > 18 | isNaN(jszoom) | jslat == 0 | jslon == 0) {jszoom = 14;}
  var jslayer = '<?php echo $_GET["layer"] ?: "O"; ?>'.toUpperCase();
  if (jslayer == "UNDEFINED") {jslayer = "O";}
  var jslang  = '<?php echo $_GET["lang"]; ?>'.toLowerCase();

  var jsmax = <?php echo $max; ?>;
  var jsp =   <?php echo json_encode($p); ?>;
  var jsc =   <?php echo json_encode($c); ?>;
  var jsx =   <?php echo json_encode($x); ?>;
  var jsy =   <?php echo json_encode($y); ?>;
  var jsn =   <?php echo json_encode($n); ?>;
  var jsf =   <?php echo json_encode($f); ?>;

  var jfixcol = <?php echo $fixedcolor ?: "0"; ?>;

  // Make map 
  var map = new L.Map('map', {center: new L.LatLng(jslat,jslon), zoom: jszoom, zoomControl: false});
  var popup = L.popup();

	map.on('click', onMapClick);

  // Base layer "Mapquestopen" https
  var mapquestopenUrl = 'https://{s}.mqcdn.com/tiles/1.0.0/map/{z}/{x}/{y}.png', subDomains = ['otile1-s','otile2-s','otile3-s','otile4-s'];
  var mapquestopenAttrib = 'Map Data &copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors, Tiles by <a href="http://open.mapquest.co.uk">MapQuest</a>';
  var mapquestopen = new L.TileLayer(mapquestopenUrl, {minZoom: 2, maxZoom: 18, attribution: mapquestopenAttrib, subdomains: subDomains});
  if (jslayer.indexOf("O") != -1) {
    map.addLayer(mapquestopen);
  }

  // Base layer "Mapquest" https
  var mapquestUrl = 'https://{s}.mqcdn.com/tiles/1.0.0/sat/{z}/{x}/{y}.jpg', subDomains = ['otile1-s','otile2-s','otile3-s','otile4-s'];
  var mapquestAttrib = 'Data, imagery and map information provided by <a href="http://open.mapquest.co.uk">MapQuest</a>';
  var mapquest = new L.TileLayer(mapquestUrl, {minZoom: 2, maxZoom: 18, attribution: mapquestAttrib, subdomains: subDomains});
  if (jslayer.indexOf("A") != -1) {
    map.addLayer(mapquest);
  }

  // Base layer "Mapnik" http & https
  var mapnikUrl = '//{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
  var mapnikAttribution = 'Map Data &copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a> ' + 
    'contributors';
  var mapnik = new L.TileLayer(mapnikUrl, {minZoom: 2, maxZoom: 18, attribution: mapnikAttribution});
  if (jslayer.indexOf("M") != -1 || jslayer.indexOf("C") != -1){
    map.addLayer(mapnik);
  } 
  
  // Base layer "Mapnik b&w" http
  var bwUrl = 'http://{s}.www.toolserver.org/tiles/bw-mapnik/{z}/{x}/{y}.png';
  var bwAttribution = '&copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors';
  var bw = new L.TileLayer(bwUrl, {minZoom: 2, maxZoom: 18, attribution: bwAttribution});
  if (jslayer.indexOf("W") != -1) {
    map.addLayer(bw);
  }

  // Base layer "Transport" http
  var transportUrl = 'http://{s}.tile2.opencyclemap.org/transport/{z}/{x}/{y}.png';
  var transportAttribution = 'Map Data &copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a> ' + 
    'contributors, Tiles courtesy of <a href="http://www.opencyclemap.org/">Andy Allan</a>';
  var transport = new L.TileLayer(transportUrl, {minZoom: 2, maxZoom: 18, attribution: transportAttribution});
  if (jslayer.indexOf("N") != -1) {
    map.addLayer(transport);
  }

  // Base layer "Landscape" http
  var landscapeUrl = 'http://{s}.tile.thunderforest.com/landscape/{z}/{x}/{y}.png';
  var landscapeAttribution = 'Map Data &copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a> ' + 
    'contributors, Tiles courtesy of <a href="http://www.opencyclemap.org/">Andy Allan</a>';
  var landscape = new L.TileLayer(landscapeUrl, {minZoom: 2, maxZoom: 18, attribution: landscapeAttribution});
  if (jslayer.indexOf("R") != -1) {
    map.addLayer(landscape);
  }

  // Layer "Labels" https
  var maplabelsUrl = 'https://{s}.mqcdn.com/tiles/1.0.0/hyb/{z}/{x}/{y}.png', subDomains = ['otile1-s','otile2-s','otile3-s','otile4-s'];
  var maplabelsAttrib = '';
  var maplabels = new L.TileLayer(maplabelsUrl, {minZoom: 2, maxZoom: 18, attribution: maplabelsAttrib, subdomains: subDomains});
  if (jslayer.indexOf("L") != -1) {
    map.addLayer(maplabels);
  }

  // Layer "Boundaries" http
  var boundariesUrl = 'http://openmapsurfer.uni-hd.de/tiles/adminb/tms_b.ashx?x={x}&y={y}&z={z}';
  var boundariesAttrib = '';
  var boundaries = new L.TileLayer(boundariesUrl, {minZoom: 2, maxZoom: 18, attribution: boundariesAttrib});
  if (jslayer.indexOf("B") != -1) {
    map.addLayer(boundaries);
  }
  
  // Layer "Cycling" http
  var cyclingUrl = 'http://tile.lonvia.de/cycling/{z}/{x}/{y}.png';
  var cyclingAttribution = 'Cycling routes: (<a href="http://cycling.lonvia.de">s Cycling Map</a>)';
  var cycling = new L.TileLayer(cyclingUrl, {minZoom: 2, maxZoom: 18, attribution: cyclingAttribution});
  if (jslayer.indexOf("C") != -1) {
    map.addLayer(cycling);
  }

  // Layer "Hiking trails" http
  var hikingUrl = 'http://tile.waymarkedtrails.org/hiking/{z}/{x}/{y}.png';
  var hikingAttribution = 'Hiking trails: (<a href="http://hiking.waymarkedtrails.org/de/">s Hiking Map</a>)';
  var hiking = new L.TileLayer(hikingUrl, {minZoom: 2, maxZoom: 18, attribution: hikingAttribution});
  if (jslayer.indexOf("H") != -1) {
    map.addLayer(hiking);
  }

  // Layer "Hill shading" http
  var hillUrl = 'http://toolserver.org/~cmarqu/hill/{z}/{x}/{y}.png';
  var hillAttribution = 'Hill shading: SRTM3 v2 (<a href="http://www2.jpl.nasa.gov/srtm/">NASA</a>)';
  var hill = new L.TileLayer(hillUrl, {minZoom: 2, maxZoom: 18, attribution: hillAttribution});
  if (jslayer.indexOf("S") != -1) {
    map.addLayer(hill);
  }

  // Layer "POI"
  // var markers = new L.featureGroup();
  var markers = new L.MarkerClusterGroup({
    showCoverageOnHover: false, maxClusterRadius: 13, iconCreateFunction: function(cluster) {
      return L.icon({iconUrl: './ico24/cluster.png', iconAnchor: [12,12]});
    }
  });
  var mi=1;
  while(mi <= jsmax){
  if (jsx[mi] != "0"){
    var tooltip = jsn[mi].replace('<br />','');
    var imgurl = '"http://' + jslang + '.m.wikivoyage.org/wiki/File:' + jsf[mi].substr(5) + '"';
    if (jsf[mi] == "0/01/no"){
      var content = jsn[mi];
      var minw = 10;
      var maxw = 240;
    }
    else {
      var content = '<a href = ' + imgurl + '><img src="http://upload.wikimedia.org/wikipedia/commons/thumb/' + 
      jsf[mi] + '/120px-' + jsf[mi].substr(5) + '" width="120"></a><br />' + jsn[mi] + '&nbsp;<a href = ' + imgurl + '><img src="./img/magnify-clip.png" widht="15" height="11" title="Enlarge">';
      var minw = 120;
      var maxw = 120;
    }
    var zio = 1000 - (mi * 2);
    var marker = new L.Marker([jsx[mi], jsy[mi]], {title: tooltip  ,zIndexOffset: zio
      ,icon: new L.NumberedDivIcon({number: jsp[mi]  
      ,iconUrl: "./ico24/" + jsc[mi] + ".png"
     })}).bindPopup(content,{minWidth:minw, maxWidth:maxw}).addTo(markers);
    }
    mi++;
  }

  if (jslayer.indexOf("-P") == -1) {
   map.addLayer(markers);
   L.edgeMarker({"radius":10,"weight":3}).addTo(map);
  }

  if (autozoom == "yes") {
    map.fitBounds(markers.getBounds());
    jslat = map.getCenter(markers).lat;
    jslon = map.getCenter(markers).lng;
    jszoom = map.getZoom(markers);
    map.setView(map.getCenter(markers),jszoom);
  }

  // Layer articles
  var articles = new L.MarkerClusterGroup({
    showCoverageOnHover: false, maxClusterRadius: 20, iconCreateFunction: function(cluster) {
      return L.icon({iconUrl: './lib/images/artcluster.png', iconAnchor: [12,40]});
    }
  });
  var maxdist = 1;
  var destzoom = 9;
  // bigger area for "ru"
  if (jslang == "ru") {
    maxdist = 10; destzoom = 7;
  }
  content= '<img src="./img/logo.png" width="64" height="64"><br /><a href="http://' + jslang + '.wikivoyage.org/wiki/';
  for (var i = 0; i < addressPoints.length; i++) {
    var a = addressPoints[i];
    if (a[0] >= jslat-maxdist && a[0] <= jslat+maxdist && a[1] >= jslon-(maxdist*1.5) && a[1] <= jslon+(maxdist*1.5)) {
      var title = a[2];
      var marker = new L.Marker(new L.LatLng(a[0], a[1]), { title: a[2]});
      marker.bindPopup(content + title + '" target="_blank">' + a[2] + '</a><br />').openPopup();
      articles.addLayer(marker);
    }
  }
  if (jslayer.indexOf("D") != -1) {
    map.addLayer(articles);
  }

  // GPX-Layer
  var tracks = new L.GPX('tracks.gpx', {async: true}) ; 
  map.addLayer(tracks);

  // MapMask
  var mask =  <?php echo $mask; ?>;
  var mcolor = "black", mweight = 0, mopacity = 0, mfillOpacity = 0.2;
  if (L.Browser.android) {
    mcolor = "blue", mweight = 5, mopacity = 0.2, mfillOpacity = 0;
  }
  if (mask != "") {
    var mapmask = L.polygon(
      [[[90, -180],[90, 180],[-90, 180],[-90, -180]],mask], // world, mask
      {color: mcolor, weight: mweight, opacity: mopacity, fillOpacity: mfillOpacity, clickable: false}
    ).addTo(tracks); 
  }

   
</script>
 
  <div id="logo">
    <img src="./img/logo.png" alt= "Logo" width= "40" height="40">
  </div>
</div>
</body>
</html>
