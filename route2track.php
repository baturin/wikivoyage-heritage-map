<!DOCTYPE html>
<!-- 
  gpx2mapmask.php - Version 2015-06-28

  Author:
  https://de.wikivoyage.org/wiki/User:Mey2008
     
  License: 
  Affero GPL v3 or later http://www.gnu.org/licenses/agpl-3.0.html 
  
  Recent changes:
  non
  
  ToDo:
  nothing
-->
<html>
<head>
  <title>GPX route to track</title>
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
<h1>Convert GPX route or track to formatted GPX track</h1>
<form method="post" enctype="multipart/form-data" >
  <input type="file" name="datei" ><br><br>
  track name <input type="text" name="trackname" value="GPX track"><br><br>
  track color <select name="color" > 
    <option>navy</option> 
    <option>green</option>
    <option>maroon</option>
  </select><br><br>
  <input type="hidden" name="hinweis" value="Please highlight and copy the yellow marked text into the clipboard.">
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
  $ausgabe = $ausgabe . '&nbsp;&nbsp;&lttrkpt lat="' . number_format($lats[1][$i],4) . '" lon="' . number_format($lons[1][$i],4) . '" /><br>';
}

echo "<h2>" . $_POST['trackname'] . "</h2>";
echo "<b>" . $_POST['hinweis'] . "</b><br><br>";
echo '<mark>';
echo '&lt?xml version="1.0" encoding="UTF-8" ?><br>';
echo '&nbsp;&ltgpx version="1.1" xmlns="http://www.topografix.com/GPX/1/1"><br>';
echo '&nbsp;&ltmetadata><br>';
echo '&nbsp;&nbsp;&ltcopyright author="Wikivoyage [[Category:Gpx data]]"><br>';
echo '&nbsp;&nbsp;&nbsp;&ltyear>' . date("Y") . '&lt;/year><br>';
echo '&nbsp;&nbsp;&nbsp;&ltlicense>CC-BY-SA&lt;/license><br>';
echo '&nbsp;&nbsp;&lt/copyright><br>';
echo '&nbsp;&lt;/metadata><br>';
echo '&nbsp;&lttrk><br>';
if ($_POST['color'] == "green" || $_POST['color'] == "maroon" ) {
  echo '&nbsp;&lttrkseg>&ltcmt>navy &lt/cmt>&lttrkpt lat="' . number_format($lats[1][0],4) . '" lon="' . number_format($lons[1][0],4) . '" />&lt/trkseg><br>';
}
if ( $_POST['color'] == "maroon" ) {
  echo '&nbsp;&lttrkseg>&ltcmt>green&lt/cmt>&lttrkpt lat="' . number_format($lats[1][0],4) . '" lon="' . number_format($lons[1][0],4) . '" />&lt/trkseg><br>';
}
echo '&nbsp;&lttrkseg> <br>&nbsp;&nbsp;&ltname>' . $_POST['trackname'] . '&lt/name><br>';
echo '&nbsp;&nbsp;&ltcmt>' . $_POST['color'] . '&lt/cmt><br>';
echo $ausgabe;
echo '&nbsp;&lt/trkseg><br>&nbsp;&lt/trk><br>&nbsp;&lt/gpx><br><br><br>';
echo "</mark>";
$trackcolor = $_POST['color'];
$mask = '[' . str_replace(array('&nbsp;&nbsp;&lttrkpt lat="', '" lon="', '" /><br>'),array( '[', ',', '],'), $ausgabe) . ']';
$mask = str_replace('],]', ']]', $mask);
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

var farbe = "<?php echo $trackcolor; ?>";
var mask = <?php echo $mask; ?>;
var line = L.polyline(mask, {color:farbe, weight:2}).addTo(map);
map.fitBounds(line.getBounds()); 
</script>
</body>
</html>
