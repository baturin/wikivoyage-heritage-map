<!DOCTYPE html>
<!-- 
  mapmask2gpx.php - Version 2015-09-14

  Author:
  https://de.wikivoyage.org/wiki/User:Mey2008
     
  License: 
  Affero GPL v3 or later http://www.gnu.org/licenses/agpl-3.0.html 
  
  Recent changes:
  2015-09-14: debug script
  2015-04-11: + it
  2015-01-01: show gpx on map
  
  ToDo:
  nothing
-->

<html>
<head>
  <title>Mapmask to GPX</title>
  <meta charset="utf-8" />
  <link rel="stylesheet" href="http://cdn.leafletjs.com/leaflet-0.7/leaflet.css" />
  <style type="text/css">body { background-color:#E0E0E0; }</style>
</head>
<body>
<div style="float: right;">
  <a href="https://en.wikivoyage.org/wiki/Template:Mapmask">
    <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/d/d1/Wikivoyage-Logo-v3-en.svg/200px-Wikivoyage-Logo-v3-en.svg.png" 
    border="0" width="100" title="Wikivoyage Template:Mapmask">
  </a>
</div>
<h1>Convert Mapmask data to GPX track</h1>
<form  method="post" enctype="multipart/form-data">
   language : <select name="lang">
  <option value="xx">       </option>
  <option value="de">deutsch</option>
  <option value="en">english</option>
  <option value="fr">français</option>
  <option value="it">italiano</option>
</select>	article : <input type="text" name="datei">
  <input type="submit" value="convert">
</form>

<?php
error_reporting(-1);

$lang = $_POST["lang"];
$datei = str_replace(" ", "_",$_POST["datei"]);

$content = file_get_contents("https://" . $lang . ".wikivoyage.org/w/index.php?title=" . $datei . "&action=raw");
$content = str_ireplace(array(' '), array(''),  $content);

preg_match('/{mapmask(.*?)}/i', $content, $mask);
preg_match_all('/\|(.*?)\,/i', $mask[1] . '|', $lats);
preg_match_all('/\,(.*?)\|/i', $mask[1] . '|', $lons);

echo '<br><br>&lt?xml version="1.0" encoding="UTF-8" ?> <br>';
echo '&ltgpx version="1.1" creator="Wikivoyage" xmlns="http://www.topografix.com/GPX/1/1" > <br>';
echo '&lttrk> &ltname>' . $lang . '.' . $datei . '&lt/name> &lttrkseg> <br>';
for($i=0; $i < substr_count($mask[1],','); $i++){
 echo '&lttrkpt lat="' . $lats[1][$i] . '" lon="' . $lons[1][$i] . '" /> ';
}
echo '<br>&lt/trkseg> &lt/trk> &lt/gpx> <br><br><br>';

$poly = '[[' . str_replace('|', '],[', substr($mask[1],1)) . ']]';

// echo '<pre>'; print_r($GLOBALS); echo '</pre>'; // *** TEST ***

?>
<div id="map" style="width: 420px; height: 420px"></div>
<script src="http://cdn.leafletjs.com/leaflet-0.7/leaflet.js"></script>

<script type="text/javascript">

var map = L.map('map').setView([30,20], 1);
var mapLink = '<a href="http://openstreetmap.org">OpenStreetMap</a>';
L.tileLayer(
  'http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: 'Map data &copy; ' + mapLink,
    maxZoom: 18,
  }).addTo(map);

var mcolor = "black", mweight = 0, mopacity = 0, mfillOpacity = 0.2;
if (L.Browser.android) {
var mcolor = "blue", mweight = 5, mopacity = 0.2, mfillOpacity = 0;
}
var mask = <?php echo $poly; ?>;
var mapmask = L.polygon(
  [[[90, -180],[90, 180],[-90, 180],[-90, -180]],mask], // world, mask
  {color: mcolor, weight: mweight, opacity: mopacity, fillOpacity: mfillOpacity, clickable: false}
).addTo(map);
var polygon = L.polygon(mask, {color:"blue", weight:2, fillOpacity:0}).addTo(map);
map.fitBounds(polygon.getBounds()); 

</script>
</body>
</html>
