<!DOCTYPE html>
<!-- 
  gpx2mapmask.php - Version 2014-12-28

  Author:
  https://de.wikivoyage.org/wiki/User:Mey2008
     
  License: 
  Affero GPL v3 or later http://www.gnu.org/licenses/agpl-3.0.html 
  
  Recent changes:
  2014-12-28: route -> track
  2014-07-20: + map
  
  ToDo:
  --
-->
<html>
<head>
  <title>GPX to mapmask</title>
  <meta charset="utf-8" />
  <link rel="stylesheet" href="http://cdn.leafletjs.com/leaflet-0.7/leaflet.css" />
  <style type="text/css">body { background-color:#E0E0E0; }</style>
</head>
<body>
<div style="float: right;">
  <a href="http://en.wikivoyage.org/wiki/Template:Mapmask">
    <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/d/d1/Wikivoyage-Logo-v3-en.svg/200px-Wikivoyage-Logo-v3-en.svg.png" 
    border="0" width="100" title="Wikivoyage Template:Mapmask">
  </a>
</div>
<h1>Convert gpx track to mapmask</h1>
<form  method="post" enctype="multipart/form-data">
  <input type="file" name="datei"><br><br>
  <input type="submit" value="convert">
</form>

<?php
error_reporting(-1);

$content = file_get_contents($_FILES['datei']['tmp_name']);
$content = str_ireplace(array('rtept', ' ', '"', '<', '>', 'maxlon', 'minlon'), array('trkpt', '', '', '{', '}', 'XXXXXX', 'XXXXXX'),  $content);
preg_match_all('/{trkptlat\=(.*?)lon/i', $content, $lats);
preg_match_all('/lon\=(.*?)}/i', $content, $lons);

foreach ($lats as $anzahl) {};
$ausgabe = "";
for($i=0; $i < count($anzahl); $i++){
  $ausgabe = $ausgabe . " |" . number_format($lats[1][$i],4) . "," . number_format($lons[1][$i],4) ;
}

echo "<h3>" . $_FILES['datei'] ['name'] . "</h3>";
echo "{{Mapmask" . $ausgabe . "}}<br><br>";

$mask = '[[' . str_replace(' |', '],[', substr($ausgabe,2)) . ']]';
?>
<div id="map" style="width: 420px; height: 420px"></div>
<script src="http://cdn.leafletjs.com/leaflet-0.7/leaflet.js"></script>

<script>
var map = L.map('map').setView([30,20], 1);
var mapLink = '<a href="http://openstreetmap.org">OpenStreetMap</a>';
L.tileLayer(
  'http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: 'Map data &copy; ' + mapLink,
    maxZoom: 18,
  }).addTo(map);

var mcolor = "black", mweight = 0, mopacity = 0, mfillOpacity = 0.2;
if (L.Browser.android) {
  mcolor = "blue", mweight = 5, mopacity = 0.2, mfillOpacity = 0;
}
var mask = <?php echo $mask; ?>;
var mapmask = L.polygon(
  [[[90, -180],[90, 180],[-90, 180],[-90, -180]],mask], // world, mask
  {color: mcolor, weight: mweight, opacity: mopacity, fillOpacity: mfillOpacity, clickable: false}
).addTo(map);
var polygon = L.polygon(mask, {color:"blue", weight:2, fillOpacity:0}).addTo(map);
map.fitBounds(polygon.getBounds()); 
</script>
</body>
</html>
