<?php

/*
  poi2gpx - Version 2017-10-20
  
  Author:
  https://de.wikivoyage.org/wiki/User:Mey2008

  Contributors:
  https://it.wikivoyage.org/wiki/Utente:Andyrom75

  License:
  Affero GPL v3 or later http://www.gnu.org/licenses/agpl-3.0.html

  Recent changes:
  2017-10-20: Convert special characters in description (Mey2008)
  2017-08-09: adding description (Andyrom75)
  2015-05-13: character & masked as &amp; - application/gpx+xml
  2015-04-27: default color; read monument articls
  2015-04-18: Read Template:GPX/ for ru
  2015-04-17: Convert special characters in file name to "_"
  2015-04-16: Special characters ' " & now allowed in POI names
  2015-04-02: Better regex to filter header of gpx track
  2015-04-01: New color for drink, filename, type + nummer + name
  2015-03-21: All marker colors equal https://en.wikivoyage.org/wiki/Template:TypeToColor

  ToDo:
  ---
*/

//PHP error reporting  *** TEST ***
error_reporting (E_ALL | E_STRICT);
ini_set ('display_errors' , 1);

include('./readpage.php');

// echo '<pre>'; print_r($GLOBALS); echo '</pre>'; // *** TEST ***

// POI'S to GPX

$file_out = '<?xml version="1.0" encoding="UTF-8" standalone="yes" ?>
';
$file_out .= '<gpx version="1.1" creator="Wikivoyage" 
  xmlns="http://www.topografix.com/GPX/1/1" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xsi:schemaLocation="http://www.topografix.com/GPX/1/1 http://www.topografix.com/GPX/1/1/gpx.xsd">
';
for($i=1; $i <= $max; $i++) {
  if ($p[$i] != 0) {
    $lat = number_format($x[$i], 4);
    $lon = number_format($y[$i], 4);
    $number = str_pad($p[$i], 2 ,'0', STR_PAD_LEFT);
    $name = str_replace(array("&", '"', "'", "<", ">", "[["), array("&amp;", "&quot;", "&apos;", "&lt;", "&gt;", ""), $n[$i]); 
    $cat = $c[$i];
    $desc = str_replace(array("'''", "''", "[[", "&nbsp;", "&ndash;", "&", '"', "'", "<", ">"), array("", "", "", " ", "-", "&amp;", "&quot;", "&apos;", "&lt;", "&gt;"), $d[$i]);
    $color = str_ireplace(array('see', 'do', 'buy', 'eat', 'drink', 'sleep', 'other', 'city', 'go', 'view', 'vicinity', 'gold', 'lime', 'red', 'silver'), array('#4682b4', '#808080', '#008080', '#d2691e', '#810061', '#000080', '#228b22', '#0000ff', '#a52a2a', '#416941', '#800000', '#ffd700', '#00ff00', '#ff0000', '#c0c0c0'), $cat);
    if ($color == $cat) {
      $color = "ffd700";
      $line = '  <wpt lat="' . $lat . '" lon="' . $lon . '">
    <name>' . $name . '</name>
    <type>' . $cat . '</type>
    <extensions> 
      <color>' . $color . '</color>
    </extensions>
    <desc>' . $desc . '</desc>
  </wpt>
';
    }
  else {  
    $line = '  <wpt lat="' . $lat . '" lon="' . $lon . '">
    <name>[' .  ucfirst($cat) . ' ' . $number . '] ' . $name . '</name>
    <type>' . $cat . '</type>
    <extensions> 
      <color>' . $color . '</color>
    </extensions>
    <desc>' . $desc . '</desc>
  </wpt>
';
  }
    $file_out .= $line;
  }
}

// read gpx

$gpxcontent = "";
if ($lang == 'el' || $lang == 'en' || $lang == 'fr' || $lang == 'it' || $lang == 'nl' || $lang == 'ru') {
  // Gpx data --> Template:GPX/Articlename
  $gpxcontent = @file_get_contents("https://" . $lang . ".wikivoyage.org/w/index.php?title=Template:GPX/" . $file . "&action=raw");
}
else {
  // Gpx data --> Articlename/Gpx
  $gpxcontent = @file_get_contents("https://" . $lang . ".wikivoyage.org/w/index.php?title=" . $file . "/Gpx&action=raw");
}
if (!$gpxcontent) {
  $gpxcontent = "</gpx>";
}
$gpxcontent = str_replace("\n", "\r\n", $gpxcontent);
$gpxcontent = preg_replace("/(.*)?<trk>/s", "  <trk>", $gpxcontent);

$file_out = $file_out . $gpxcontent;
$out = strlen($file_out);

if ($out > 350) {
  $filename = str_replace(array(":", "/"), "_", $file) . "_" . $lang . ".gpx";
  if (isset($file_out)) {
    header("Content-Length: $out");
    header("Content-Type: application/gpx+xml");
    header("Content-Disposition: attachment; filename=$filename");
    header("Content-Transfer-Encoding: binary");
    echo $file_out;
    exit;
  }
}
else {
  $file_out = "No geocoded POI's in this WV acticle.";
  $out = strlen($file_out);
  $filename = str_replace(array(":", "/"), "_", $file) . "_" . $lang . "_--_ERROR_--_NO_GEOCODED_POI.txt";
  if (isset($file_out)) {
    header("Content-Length: $out");
    header("Content-Type: application/gpx+xml");
    header("Content-Disposition: attachment; filename=$filename");
    header("Content-Transfer-Encoding: binary");
    echo $file_out;
    exit;
  }
}
?>
