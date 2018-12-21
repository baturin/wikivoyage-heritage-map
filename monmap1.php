<?php

class WikivoyagePageReader
{
    public function read($page)
    {
        return file_get_contents($this->getUrl($page));
    }

    private function getUrl($page)
    {
        return "https://ru.wikivoyage.org/w/index.php?title=" . $page . "&action=raw";
    }
}

class RequestParameters
{
    public function getName()
    {
        return $_GET['name'];
    }

    public function getLat()
    {
        return (float)$_GET['lat'];
    }

    public function getLon()
    {
        return (float)$_GET['lon'];
    }

    public function getLayer()
    {
        return $_GET['layer'];
    }

    public function getZoom()
    {
        return (int)$_GET["zoom"];
    }
}

$wikivoyagePageReader = new WikivoyagePageReader();
$requestParameters = new RequestParameters();

?>
<!DOCTYPE html>
<html>
<!-- 
Wikivoyage cultural and natural heritage maps:
Original author:
  https://de.wikivoyage.org/wiki/User:Mey2008
Contributors:
  https://ru.wikivoyage.org/wiki/User:AlexeyBaturin
License:
  Affero GPL v3 or later http://www.gnu.org/licenses/agpl-3.0.html
-->
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo htmlspecialchars($requestParameters->getName()) . " — Wikivoyage Map"; ?></title>
    <link rel="icon" href="./lib/images/favicon.png" type= "image/png" />
    <link rel="stylesheet" href="./lib/leaflet/leaflet.css" />
    <link rel="stylesheet" href="./lib/poimap.css" />
  </head>
<body>
<div id="map">
  <div id="logo">
    <img src="./lib/images/logo.png" alt= "Logo" title= "Version 2016-07-13" width="64" height="64">
  </div>
  <script type="text/javascript" src="./lib/leaflet/leaflet.js"></script>
  <script type="text/javascript" src="./lib/buttons-new.js"></script>
  <script type="text/javascript" src="./lib/zoomdisplay.js"></script>
  <script type="text/javascript" src="./lib/i18n.js"></script>
  <script type="text/javascript" src="./locale/ru.js"></script>
  <script type="text/javascript" src="./lib/maptiles.js"></script>

<?php


$file= str_replace("\'","'", $requestParameters->getName());
$content = $wikivoyagePageReader->read($file);

// Strip comments
$content = preg_replace('/<!--(.|\s)*?-->/', '', $content); 

// Strip blanks
$content = str_ireplace(array('[[', ']]', '| ', ' |', '= ', ' =' ), array('', '', '|', '|', '=', '=' ),  $content);

// translate
$content= str_ireplace(array('Monument|', 'Natural monument|'), array('monument|', 'monument|'), $content);

// strip unwanted templates
$content = preg_replace("/{{(?!Monument\|)(.|\s)*?}}/im", "", $content); 

// read parameters {{monument|
$apart = explode('{{monument|', $content);
$total = count($apart);

for($i=1; $i < $total; $i++){
  $text = explode('}}', $apart[$i]);
  $part = str_replace('|', '&', $text[0]);
  $name = $type = $lat = $long = $image = '';
  parse_str($part); 
  $c[$i] = (trim($type)  ?: "other");
  $x[$i] = (trim($lat)  + 0 ?: "0");
  $y[$i] = (trim($long) + 0 ?: "0");
  $n[$i] = (trim($name)  ?: "NoName");
  $f[$i] = (str_replace(" ","_",trim($image)) ?: "0/01/no");
  if (substr($f[$i],1,1) != "/") {
    $md5 = md5($f[$i]);
    $f[$i] = substr($md5,0,1) . "/" . substr($md5,0,2) . "/" . $f[$i];
  }
}
$max = $i;

?>

<script type='text/javascript'>

  var lang = "ru";
  L.registerLocale(lang, mylocale);
  L.setLocale(lang);
  
  maptiles();
  
function onAll() {
  map.setView([jslat,jslon],jszoom,true);
  map.fitBounds(monuments.getBounds());
} 

function onMapClick(e) {
  var fmlat=e.latlng.lat.toFixed(5);
  var fmlng=e.latlng.lng.toFixed(5);
	popup
	.setLatLng(e.latlng)
	.setContent(L._('You clicked the map at') + ' <br> lat=' + fmlat + ' | long=' + fmlng)
	.openOn(map);
}

// All arrays to js
var jslat = <?php echo json_encode($requestParameters->getLat() ?: 0);?>;
var jslon = <?php echo json_encode($requestParameters->getLon() ?: 0); ?>;
var jszoom = <?php echo json_encode($requestParameters->getZoom() ?: 14); ?>;
var autozoom = "no";
if (jszoom === "auto") {
 autozoom = "yes";
}
if (parseInt(jszoom) < 2 | parseInt(jszoom) > 17 | isNaN(jszoom) | jslat === 0 | jslon === 0) {
  jszoom = 14;
}
var jslayer = '<?php echo $_GET["layer"] ?: "W"; ?>'.toUpperCase();
if (jslayer === "UNDEFINED") {
  jslayer = "WX";
}
if (jslayer === "OX") {
  jslayer = "WX";
}

var jsmax = <?php echo $max; ?>;
var jsc =   <?php echo json_encode($c); ?>; // type
var jsx =   <?php echo json_encode($x); ?>; // lat
var jsy =   <?php echo json_encode($y); ?>; // long
var jsn =   <?php echo json_encode($n); ?>; // name
var jsf =   <?php echo json_encode($f); ?>; // image

// Make map 
var map = new L.Map('map', {center: new L.LatLng(jslat,jslon), zoom: jszoom, zoomControl: false});
var monumentsAttribution = '';
var popup = L.popup();

map.on('click', onMapClick);

// Base layer "Wikimedia"
if (jslayer.indexOf("W") != -1) {
  map.addLayer(wikimedia);
}

// Base layer "Mapnik"
if (jslayer.indexOf("M") != -1) {
  map.addLayer(mapnik);
} 

  // Base layer "Landscape" http
  if (jslayer.indexOf("R") != -1) {
    map.addLayer(landscape);
  }
  
  // load local image
  function imgError(image) {   
    image.onerror = "";
    image.src = image.src.replace("wikipedia/commons","wikivoyage/ru");
    return true;
  } 

// Layer monuments
var monuments = new L.featureGroup();
var mi=1;
while(mi < jsmax){
  if (jsx[mi] != "0"){
    var tooltip = jsn[mi].replace('<br />','');
    var imgurl = '"https://ru.m.wikivoyage.org/wiki/File:' + jsf[mi].substr(5) + '"';
    // no image
    if (jsf[mi] == "0/01/no"){
      var content = jsn[mi];
      var minw = 10;
      var maxw = 240;
    }
    // with image
    else {
      var content = '<a href = ' + imgurl + '><img src="https://upload.wikimedia.org/wikipedia/commons/thumb/' + jsf[mi] + '/120px-' + jsf[mi].substr(5) + '" width="120" onerror="imgError(this);"></a><br />' + jsn[mi] + '&nbsp;<a href = ' + imgurl + '><img src="./lib/images/magnify-clip.png" widht="15" height="11" title="⇱⇲">';
      var minw = 120;
      var maxw = 120;
    }
    var zio = 1000 - (mi * 2);
    var myIcon = L.icon({iconUrl: "./ico24/" + "mon-" + jsc[mi] + ".png", iconAnchor: [12, 12], popupAnchor: [0, -12]});
    var marker = L.marker([jsx[mi], jsy[mi]], {title: tooltip, zIndexOffset: zio, icon: myIcon}).bindPopup(content,{minWidth:minw, maxWidth:maxw}).addTo(monuments);
  }
  mi++;
}
map.addLayer(monuments);

if (jslayer.indexOf("X") != -1) {
  var redIcon = L.icon({iconUrl: './ico24/target.png', iconSize: [32,32], iconAnchor: [16,16]});
  L.marker([jslat, jslon],{icon: redIcon}).addTo(monuments);
}

if (autozoom == "yes") {
  map.fitBounds(monuments.getBounds());
  jslat = map.getCenter(monuments).lat.toFixed(5);
  jslon = map.getCenter(monuments).lng.toFixed(5);
}

// Controls

var basemaps = {};
var overlays = {};

basemaps[L._('Wikimedia') + ' <img src="./lib/images/wmf-logo-12.png" />'] = wikimedia;
basemaps[L._('Mapnik') + ' <img src="./lib/images/external.png" />'] = mapnik;
basemaps[L._('Relief_map') + ' <img src="./lib/images/external.png" />'] = landscape;
overlays[L._('Monuments') + ' <img src="./lib/images/wv-logo-12.png" />'] = monuments;

map.addControl(new L.Control.Layers(basemaps, overlays));
map.addControl(new L.Control.Scale());
map.addControl(new L.Control.Buttons());

// External content warning
var imgpath = '../lib/images/';
if (L.Browser.ie) {
imgpath = './lib/images/';
}
var warning = 'url(' + imgpath + 'line.png) "' + L._('Content with {external} is hosted externally, so enabling it shares your data with other sites.',{external:' "url(' + imgpath + 'external.png)" '}) + '"';
document.styleSheets[1].cssRules[4].style.content = warning;

</script>
 
</div>
</body>
</html>
