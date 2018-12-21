<?php

/*
multipoi2gpx - Version 2015-05-13

Author:
https://de.wikivoyage.org/wiki/User:Mey2008

License:
Affero GPL v3 or later http://www.gnu.org/licenses/agpl-3.0.html 

Recent changes:
2015-11-10: fixed path for translate
2015-05-13: character & masked as &amp; - application/gpx+xml

ToDo:
--
*/

//PHP error reporting  *** TEST ***
error_reporting (E_ALL | E_STRICT);
ini_set ('display_errors' , 1);
	
header( 'content-type: text/html; charset=utf-8' );

// DMS to DEC (DIR must be N, E, S or, W)
function DMStoDEC($dms) {
  $part = preg_split("/[^\d\w\.]+/",$dms);
  $pnr = count($part);
  if ($pnr == 3) {
    $part[3] = $part[2];
    $part[2] = 0;
  }
  elseif ($pnr == 2) {
    $part[3] = $part[1];
    $part[1] = 0;
    $part[2] = 0;
  } 
  $dec = $part[0] + ((($part[1]*60) + ($part[2]))/3600);
  if ($part[3] == "S" || $part[3] == "W") {
    $dec = $dec * -1;
  } 
  return $dec;
} 

// reading URL parameters
$lang = $_GET["lang"];
$file = $_GET["name"];
$places = unserialize($file);

$file_out = '<?xml version="1.0" encoding="UTF-8" standalone="yes" ?>
';
$file_out .= '<gpx version="1.1" creator="Wikivoyage" 
  xmlns="http://www.topografix.com/GPX/1/1" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xsi:schemaLocation="http://www.topografix.com/GPX/1/1 http://www.topografix.com/GPX/1/1/gpx.xsd">
';

$fc = 0;

foreach($places as $file) {
$fc++;
$file = str_replace(array("\'", " "), array("'", "_"), $file);

// reading article data
$content = file_get_contents("https://" . $lang . ".wikivoyage.org/w/index.php?title=" . $file . "&action=raw");

// strip comments and nowiki
$content = preg_replace('/<!--(.|\s)*?-->/', '', $content);
$content = preg_replace('/<nowiki(.|\s)*?nowiki>/', '', $content);

// replace special strings
$content = str_ireplace(array('[*', ']]', '| ', ' |', '= ', ' =', '=====', '===', '&', '{{Marker', '{{Listing', '{{vCard', '?lang=', '@', '{{Poi', '=listing' ), array('', '', '|', '|', '=', '=', 'XXXXX', 'XXX', '%26', '{{listing', '{{listing', '{{listing', 'XxxxxX', 'X', '{{poi', '=' ),  $content);

// poi to listing
$content = preg_replace(array('/{{poi\|(.*?)\|(.*?)\|(.*?)\|(.*?)\|(.*?)[\||}](.*?)}}/i', '/{{poi\|(.*?)\|(.*?)\|(.*?)\|(.*?)\|(.*?)[\||}]/i'), array("{{listing|map=$1|type=$2|lat=$3|long=$4|name=$5|image=$6}}", "{{listing|map=$1|type=$2|lat=$3|long=$4|name=$5}}"), $content);

// replace section 2 headers
$content = preg_replace('/==.*==/', '{{listing|type=**h2**|name=**SECTION**}}', $content); 

// translate to english
include 'trans/trans-' . $lang . '.php';
$content = str_ireplace(array_keys($trans), $trans, $content);

// convert to {{listing|
$content = preg_replace("/{{(go|see|do|buy|eat|drink|sleep|fun|vicinity|health|around|city|diplo)/", "{{listing|type=$1", $content);
 
// strip unwanted templates
$content = preg_replace("/{{(?!poi|listing|mapframe|mapmask|geo|photolist)(.|\s)*?}}/im", "", $content);

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

$nr = $nother = 0;
// $groups, $grpmax only for de
$groups = array('error', '**h2**', 'blue', 'buy', 'do', 'drink', 'eat', 'fun', 'go', 'gold', 'health', 'lime', 'listing', 'maroon', 'mediumaquamarine', 'other', 'red', 'see', 'silver', 'sleep', 'view', 'vicinity', 'health', 'around', 'city', 'diplo');
$grpmax = array_fill(0, 29, 0);

$nr_pois = 0;
for($i=1; $i < count($apart); $i++){
  $text = explode('}}', $apart[$i]);
  $part = str_replace('|','&', $text[0]);

  $name = $map = $type = $group = $lat = $long = $image = '';
  parse_str(str_replace('+', '%2B', $part));

  // convert DMS to DEC
  if (strpos($lat, "°")) {
    $lat = DMStoDEC($lat);
    $long = DMStoDEC($long);
  }
 
  $n[$i] = (trim($name)  ?: "NoName");
  $p[$i] = (trim($map)   ?: 0);
  $c[$i] = (trim($type)  ?: "other");
  $x[$i] = (trim($lat)  + 0 ?: "0");
  if ($x[$i] != 0) {
    $nr_pois++;
  }
  $y[$i] = (trim($long) + 0 ?: "0");
  $f[$i] = (str_replace(" ","_",trim($image)) ?: "0/01/no");
  if (substr($f[$i],1,1) != "/") {
    $md5 = md5($f[$i]);
    $f[$i] = substr($md5,0,1) . "/" . substr($md5,0,2) . "/" . $f[$i];
  }

  // automatic numbering
  if ($lang == "de" ) {
    if ($x[$i] + 0 != 0 && $p[$i] == 0) {
      
      if ($group != '') {
        $c[$i] = trim($group);
      }
      $key = array_search($c[$i], $groups);
      $grpmax[$key]++; 
      $p[$i] = $grpmax[$key];
    }
  }
  else {
    if ($x[$i] + 0 != 0) {
      if ($c[$i] == "other") {
        $nother++;
        $p[$i] = $nother;      
      }
      else {
        $nr++;
        $p[$i] = $nr;       
      }   
    }
    if ($c[$i] == "**h2**") {
      $nr = 0;
    }
  }
}
$max = $i - 1;

//  read nn-articles.js
$geo = file('./data/' . $lang . '-articles.js');
$suchfile = str_replace(array("_", "(", ")", "/"), array(" ", "\(", "\)", "\/"), $file);
$desti = preg_grep('/"' . $suchfile . '"/', $geo);
$desti = array_values($desti);
$desti_parts = explode(",",$desti[0]);
$lat = substr($desti_parts[0],1);
$lon = $desti_parts[1];

$line = '  <wpt lat="' . $lat . '" lon="' . $lon . '"> 
    <name>' . str_replace("_", " ",$file) . ' (' . $nr_pois . ' POIs)</name>
    <type>01</type>
    <extensions> 
      <color>#FF00FF</color>
    </extensions>
  </wpt>
';
$file_out = $file_out . $line;

for($i=1; $i <= $max; $i++) {
  if ($p[$i] != 0) {
    $lat = number_format($x[$i], 4);
    $lon = number_format($y[$i], 4);
    $number = str_pad($p[$i], 2 ,'0', STR_PAD_LEFT);
    $name = str_replace(array('"', "'", "<", ">", "&", "[["), array("&quot;", "&apos;", "&lt;", "&gt;", "&amp;", ""), $n[$i]); 
    $cat = $c[$i];
    $color = str_ireplace(array('see', 'do', 'buy', 'eat', 'drink', 'sleep', 'other', 'city', 'go', 'view', 'vicinity', 'gold', 'lime', 'red', 'silver'), array('#4682b4', '#808080', '#008080', '#d2691e', '#810061', '#000080', '#228b22', '#0000ff', '#a52a2a', '#416941', '#800000', '#ffd700', '#00ff00', '#ff0000', '#c0c0c0'), $cat);
    if ($color == $cat) { // destination
      $color = "ffd700";
      $line = '  <wpt lat="' . $lat . '" lon="' . $lon . '">
    <name>' . $name . '</name>
    <type>' . str_pad($fc, 2, 0, STR_PAD_LEFT) . '-' . ucfirst($cat) . '</type>
    <extensions> 
      <color>' . $color . '</color>
    </extensions>
  </wpt>
';
    }
    else { // POI
      $line = '  <wpt lat="' . $lat . '" lon="' . $lon . '">
      <name>[' .  ucfirst($cat) . ' ' . $number . '] ' . $name . '</name>
      <type>' . str_pad($fc, 2, 0, STR_PAD_LEFT) . '-' . ucfirst($cat) . '</type>
      <extensions> 
        <color>' . $color . '</color>
      </extensions>
    </wpt>
  ';
    }
  $file_out = $file_out . $line;
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
  $gpxcontent = "";
}
$gpxcontent = str_replace("\n", "\r\n", $gpxcontent);
$gpxcontent = preg_replace("/(.*)?<trk>/s", "  <trk>", $gpxcontent);
$gpxcontent = str_replace("</gpx>", "", $gpxcontent);

$file_out = $file_out . $gpxcontent;

}  // end foreach

$file_out = $file_out . "</gpx>
";

$out = strlen($file_out);
$filename = str_replace(array(":", "/", " "), "_", $places[0]) . "_etc_". $lang . ".gpx";

if (isset($file_out)) {
  header("Content-Length: $out");
  header("Content-Type: application/gpx+xml");
  header("Content-Disposition: attachment; filename=$filename");
  header("Content-Transfer-Encoding: binary");
  echo $file_out;
}
?>
