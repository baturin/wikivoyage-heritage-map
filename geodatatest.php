<!DOCTYPE html>
<head>
  <title>Wikivoyage - Geodata</title>
  <meta charset="utf-8">
</head>
<body>
<?php
/*
Geodatatest - version 2014-08-16

************* TEST **************

Author:
  http://de.wikivoyage.org/wiki/User:Mey2008
Contributors:
  http://it.wikivoyage.org/wiki/Utente:Andyrom75
License: 
  Affero GPL v3 or later http://www.gnu.org/licenses/agpl-3.0.html
Recent changes:
  2014-08-16: new default image
  2014-08-11: + fr
  2014-08-10: thumbnails
  2014-05-11: trim coords
  2014-05-09: clear geodata.log
  2014-04-14: copy articles data to /w/
  2014-03-23: geodata.log
  2014-03-17: + de, + en
  2014-03-16: tidy script
  2014-02-26: + it
ToDo:
  2014-08-15: optimizing search for france
  2014-08-13: improve speed
*/

// PHP error reporting
error_reporting (E_ALL | E_STRICT);
ini_set ('display_errors' , 1);

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

$lang = array("de","el","en","es","fr","he","it","nl","pl","pt","ro","ru","sv","uk","vi","zh");
$lang = array("fr"); // *** TEST ***

// clear geodata.log
$handle = fopen("geodata.log", "w");
fclose($handle); 

$timestamp = time();
$date = date("Y-m-d - H:i:s", $timestamp);
error_log("---- " . $date . " ----\n", 3, "geodata.log");

for($i = 0; $i < count($lang); $i++) {
  set_time_limit(360);
  $content = "";
  $file = "http://dumps.wikimedia.org/" . $lang[$i] . "wikivoyage/latest/" . $lang[$i] . "wikivoyage-latest-pages-articles.xml.bz2";
 
  // specific script de, en
  if($lang[$i] == "de" || $lang[$i] == "en") {
    $bz = bzopen ($file, "r") or die("$file could not be opened for reading");
    while (!feof($bz)) {
      $line = fgets($bz);
      if (stripos($line,"{geo") !== false) {
        $line = str_ireplace(" ","",$line);
      }
      if (stripos($line,"{geo") !== false || stripos($line,"title>") !== false || stripos($line,"ns>") !== false || stripos($line, ".jpg") !== false){
        $line = str_ireplace(array('}}}}', '<title>', '</title>', '<ns>', '</ns>', 'geodata', "lat=", "long="), array('}}', '{title}', '{/title}', '{ns}', '{/ns}', 'geo', "", ""), $line);
        $content = $content . $line;
      }
    }
    bzclose ($bz);
  }
  // end de, en

  else {
    $bz = bzopen($file, "r") or die("$file could not be opened for reading");
      while (!feof($bz)) {
        $content .= bzread($bz, 4096);
      }
    bzclose($bz); 
  }

  echo "---- " . $date . " ----<br />";
  echo "------------------------------------<br />";
  echo "The entire " . $lang[$i] . "-wiki is " . strlen($content) . " bytes in size. <br />";

  // specific script it
  if($lang[$i] == "it") {
    $content = str_ireplace(array('&quot;', '&lt;!--Latitudine--&gt;', '&lt;!--Longitudine--&gt;', "′", '″'), array('"', '', '', "'", '"'), $content);
    $content = preg_replace('/Lat(?: *)=(?: *)(.*)\n\|(?: *)Long(?: *)=(?: *)(.*)\n/i', '{{Geo|$1|$2}}', $content);
  }
  // end it

  // specific script fr
  if($lang[$i] == "fr") {
    $content = preg_replace('/latitude\s*?=\s*?([-,0-9].+)\s*?\|\s*?longitude\s*=\s*?([-,0-9].*+)\s*?\|/', '{{Geo|$1|$2|}}', $content);
  }
  // end fr

  // change special chars
  $content = str_ireplace(array('}}}}', '<', '>', '&gt;'), array('}}', '{', '}', '}'), $content);

  // strip comments and nowiki
  $content = preg_replace(array('/(?:{!--(?:.|\s)*?--})/', '/(?:{nowiki(?:.|\s)*?nowiki})/' ), 'X', $content);

  // translate image tags
  $content = str_ireplace(array('Fichier:', 'Bestand:', 'Fișier:', 'Fil:', 'Immagine =', 'Immagine=', 'Image:', 'קובץ:', 'imagen=', 'Tập tin:', 'Файл:', 'Bild:', 'widok=', 'File:', 'Datei:'), 'Image=', $content);

  // echo $content; // *** TEST ***

  preg_match_all("/({title}(.*){\/title}|{ns}(.*){\/ns}|{{geo\s*?\|(.*)}}|{{geodata\s*?\|(.*)}})/i", $content, $matches);

  // print_r($matches); // *** TEST ***

  $rows = (count($matches,1) / count($matches,0)) - 1;
  print "There are {$rows} rows in the table. <br /><br />";

  $fp = fopen("./data/" . $lang[$i] . "-articles.js","wb+");
  $fi = fopen("./data/" . $lang[$i] . "-images.js","wb+");
  fwrite($fp, "var addressPoints = [\n");
  fwrite($fi, "var images = [\n");
  for($m = 1; $m <= $rows - 1; $m++) {
    if ($matches[3][$m-1] == "0" && strpos($matches[4][$m],"|") != 0) {
      $teile = explode("|", $matches[4][$m]);
      if (strpos($teile[0], "°")) {
        $teile[0] = DMStoDEC($teile[0]);
      }
      if (strpos($teile[1], "°")) {
        $teile[1] = DMStoDEC($teile[1]);
      }
      $teile[0] = trim($teile[0]);
      $teile[1] = trim($teile[1]);
      if(!is_numeric($teile[0]) or !is_numeric($teile[1])) {
         echo $lang[$i] . " - " . $matches[2][$m-2] . " = " . $teile[0] . " | " . $teile[1] . "<br>";
         error_log($lang[$i] . " - " . $matches[2][$m-2] . " = " . $teile[0] . " | " . $teile[1] . "\n", 3, "geodata.log");
      }
      else {
        fwrite($fp, '[' . number_format($teile[0],3) . ',' . number_format($teile[1],3) . ',"' . $matches[2][$m-2] . '"' . "],\n");
        // search for image
        $test= '({title}' . addcslashes($matches[2][$m-2], '()/') . '{.*?{title})';
        $pages= preg_match("#$test#is", $content, $page);
        if (isset($page[0])) {
          $pics = preg_match('/Image=([^"\[{\|]*?.jpg)/i', $page[0], $pic);
          if (isset($pic[0])) {
            $image = substr($pic[0],6);
            // md5 hash of image name
            $image = str_replace(' ', '_', trim($image));
            $md5 = md5($image);
            $md5path = substr($md5,0,1) . '/' . substr($md5,0,2) . '/';
            $image= $md5path . $image;
          }
          else {
            $image = '7/7e/WV-logo-artmap.jpg'; // default 
          }
        }
        else {
          $image = '7/7e/WV-logo-artmap.jpg'; // default 
          }
        fwrite($fi, '"' . $image . '"' . ",\n");
      }
    }
  }
  fwrite($fp, "];\n");
  fwrite($fi, "];\n");
  fclose($fp);
  fclose($fi);
  copy("./data/" . $lang[$i] . "-articles.js","../w/data/" . $lang[$i] . "-articles.js");
  copy("./data/" . $lang[$i] . "-images.js","../w/data/" . $lang[$i] . "-images.js");
}

$timestamp = time();
$date = date("Y-m-d - H:i:s", $timestamp);
echo "---- " . $date . " ----<br>";
error_log("---- " . $date . " ----\n", 3, "geodata.log");
error_log("\n", 3, "geodata.log");

copy("geodata.log","../w/geodata.log");

// echo '<pre>'; print_r($GLOBALS); echo '</pre>'; // *** TEST ***

?>
</body>
</html>
