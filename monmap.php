<!DOCTYPE html>
<html>
<!-- 
MonMap:
  Version 2016-07-13
Author:
  https://de.wikivoyage.org/wiki/User:Mey2008
Contributors:
  no 
License: 
  Affero GPL v3 or later http://www.gnu.org/licenses/agpl-3.0.html 
Recent changes:
  2016-07-13: Wikimedia tiles(Mapquest stop service)
  2015-11-07: + natural monument
  2015-11-06: new layer "Wikimedia" + to default
  2015-09-22: new translate for layers
  2015-09-14: debug script
  2015-08-31: external contnet warning
  2015-07-26: optimize for count()
  2015-05-25: poimap.css
  2015-05-22: maptiles.js
  2015-05-20: layer markers now monuments
  2015-05-19: Localization for layers control
  2015-05-17: Localization reversed for layers (IE incompatible)
  2015-05-16: buttons-new.js
  2015-05-13: more localization
  2015-04-25: mon-icon.png
ToDo:
  nothing
-->
   
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title> <?php echo $_GET["name"]," — Wikivoyage Map" ?></title>
    <link rel="icon" href="./lib/images/favicon.png" type= "image/png" />
    <link rel="stylesheet" href="./lib/leaflet.css" />
    <link rel="stylesheet" href="./lib/poimap.css" />
  </head>
<body>
<div id="map">
  <div id="logo">
    <img src="./lib/images/logo.png" alt= "Logo" title= "Version 2016-07-13" width="64" height="64">
  </div>
  <script type="text/javascript" src="./lib/leaflet.js"></script>
  <script type="text/javascript" src="./lib/buttons-new.js"></script>
  <script type="text/javascript" src="./lib/zoomdisplay.js"></script>
  <script type="text/javascript" src="./lib/i18n.js"></script>
  <script type="text/javascript" src="./locale/<?php echo $_GET['lang'] ?: 'en'; ?>.js"></script>
  <script type="text/javascript" src="./lib/maptiles.js"></script>

<?php

/*
// PHP error reporting
error_reporting (E_ALL | E_STRICT);
ini_set ('display_errors' , 1);
*/

// Reading URL parameters
$lang= $_GET["lang"];
$file= str_replace("\'","'",$_GET["name"]);

// Reading article data
$wikisource = 'wikivoyage';
if ($lang == 'lv') {
  $wikisource = 'wikipedia';
}

$content = file_get_contents("https://" . $lang . '.' . $wikisource . ".org/w/index.php?title=" . $file . "&action=raw");

// Strip comments
$content = preg_replace('/<!--(.|\s)*?-->/', '', $content); 

// Strip blanks
$content = str_ireplace(array('[[', ']]', '| ', ' |', '= ', ' =' ), array('', '', '|', '|', '=', '=' ),  $content);

// translate
$content= str_ireplace(array('Monument|', 'Natural monument|'), array('monument|', 'monument|'), $content);

// strip unwanted templates
$content = preg_replace("/{{(?!Monument\|)(.|\s)*?}}/im", "", $content); 

// echo $content; // *** TEST ***

// read parameters {{monument|
$apart = explode('monument|', $content);
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

// echo '<pre>'; print_r($GLOBALS); echo '</pre>'; // *** TEST ***

?>

<noscript> 
 <h2><a href="http://activatejavascript.org/en/">This application needs JavaScript. - See instructions:</a></h2>
</noscript>

<script type='text/javascript'>

// stop for testing // *** TEST ***

  var lang = "<?php echo $_GET['lang'] ?: 'en'; ?>";
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
var jswiki = '<?php echo $wikisource; ?>';
var jslat   =  '<?php echo $_GET["lat"] ?: "0";?>';
if (isNaN(jslat)) {
  jslat= "0"; alert(L._("ERROR: Coordinates must be numeric!"));
}
jslat =parseFloat(jslat);
var jslon   =  '<?php echo $_GET["lon"] ?: "0"; ?>';
if (isNaN(jslon)) {
  jslon= "0";alert(L._("ERROR: Coordinates must be numeric!"));
}
jslon =parseFloat(jslon);
var jszoom  =  '<?php echo $_GET["zoom"] ?: "14"; ?>';
var autozoom = "no";
if (jszoom == "auto") {
 autozoom = "yes";
}
if (parseInt(jszoom) < 2 | parseInt(jszoom) > 17 | isNaN(jszoom) | jslat === 0 | jslon === 0) {
  jszoom = 14;
}
var jslayer = '<?php echo $_GET["layer"] ?: "W"; ?>'.toUpperCase();
if (jslayer == "UNDEFINED") {
  jslayer = "WX";
}
if (jslayer == "OX") {
  jslayer = "WX";
}

var jslang  = '<?php echo $_GET["lang"]; ?>'.toLowerCase();

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
    image.src = image.src.replace("wikipedia/commons","wikivoyage/" + jslang);
    return true;
  } 

// Layer monuments
var monuments = new L.featureGroup();
var mi=1;
while(mi < jsmax){
  if (jsx[mi] != "0"){
    var tooltip = jsn[mi].replace('<br />','');
    var imgurl = '"https://' + jslang + '.m.' + jswiki + '.org/wiki/File:' + jsf[mi].substr(5) + '"';
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

var basemaps = {
  'Wikimedia': wikimedia,
  'Mapnik': mapnik,
  'Relief_map': landscape
}; 
var overlays = {
  'Monuments': monuments
};

basemaps[L._('Wikimedia') + ' <img src="./lib/images/wmf-logo-12.png" />'] = basemaps.Wikimedia;
basemaps[L._('Mapnik') + ' <img src="./lib/images/external.png" />'] = basemaps.Mapnik;
basemaps[L._('Relief_map') + ' <img src="./lib/images/external.png" />'] = basemaps.Relief_map;
overlays[L._('Monuments') + ' <img src="./lib/images/wv-logo-12.png" />'] = overlays.Monuments;
    
var maptype = 'monmap';

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

