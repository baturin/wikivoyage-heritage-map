<!DOCTYPE html>
<head>
  <title>Wikivoyage - Geodata</title>
  <meta charset="utf-8">
</head>
<body>
<?php
/*
Geodata - version 2017-10-03

Author:
  https://de.wikivoyage.org/wiki/User:Mey2008
Contributors:
  https://it.wikivoyage.org/wiki/Utente:Andyrom75
License: 
  Affero GPL v3 or later http://www.gnu.org/licenses/agpl-3.0.html
Recent changes:
  2017-10-03: set_time_limit(480)
  2017-08-01: other geteilt in other1 un other2
  2016-12-03: + fi
  2016-08-21: spezial script f0r zh
  2016-04-18: copy data files first (ssl error)
  2015-07-26: preg_match bug -> page[0] error
  2015-04-28: he: תמונה =
  2015-04-19: sv now lat/long from quickbar
  2015-03-22: lat + long test zero or > 90 / 180
  2015-03-17: all data in nn.articles.js
  2015-03-16: article.js + photo
ToDo:
  --
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

$group = $_GET["group"];

if ($group == "de") {
  $lang = array("de");
}
elseif ($group == "en") {
  $lang = array("en");
}
elseif ($group == "other1") {
  $lang = array("el","es","fa","fi","fr","he");
}
elseif ($group == "other2") {
  $lang = array("it","nl","pl","pt","ro","ru","sv","uk","vi","zh");
}
else {
  $lang = array($group); // single language 
}

// clear geodata.log all 2 hours
if (time() - filemtime("geodata.log") > 7200) {
  $handle = fopen("geodata.log", "w");
  fclose($handle); 
}

for($i = 0; $i < count($lang); $i++) {
  error_log("\n" . $lang[$i] . " - " . date("Y-m-d - H:i:s") . " ----\n", 3, "geodata.log");
  set_time_limit(480);
  $content = "";
  $file = "http://dumps.wikimedia.org/" . $lang[$i] . "wikivoyage/latest/" . $lang[$i] . "wikivoyage-latest-pages-articles.xml.bz2";
  copy ($file, "articles.bz2");
  $file = "articles.bz2";

  $bz = bzopen($file, "r") or die("$file could not be opened for reading");

  // specific script "fr" & "it" & "sv"(no geo template)
  if($lang[$i] == "fr" || $lang[$i] == "it" || $lang[$i] == "sv") {
    while (!feof($bz)) {
      $content .= bzread($bz, 4096);
    }
  } 
  else {
    while (!feof($bz)) {
      $line = fgets($bz);
      if (stripos($line,"{geo") !== false) {
        $line = str_ireplace(" ","",$line);
        $content = $content . $line;
      }
      elseif (stripos($line, ".jpg") !== false || stripos($line,"<title>") !== false || stripos($line,"<ns>") !== false ||  stripos($line, "</page>") !== false){
        $content = $content . $line;
      }
    }
  }

  bzclose($bz);

  // specific script de
  if($lang[$i] == "de") {
    $content = str_ireplace(array('Geo|Breite', 'geodata', 'lat=', 'long='), array('', 'geo', '', ''), $content);
  }

  // specific script fr
  if($lang[$i] == "fr") {
    $content = preg_replace('/latitude\s*?=\s*?([.,\-,0-9]+)\s*?\|\s*?longitude\s*=\s*?([.,\-,0-9]+)/i', "{{Geo|$1|$2}}\n", $content);
  }
  
  // specific script it
  if($lang[$i] == "it") {
    $content = str_ireplace(array('&quot;', '&lt;!--Latitudine--&gt;', '&lt;!--Longitudine--&gt;', "′", '″'), array('"', '', '', "'", '"'), $content);
    $content = preg_replace('/Lat(?: *)=(?: *)(.*)\n\|(?: *)Long(?: *)=(?: *)(.*)\n/i', '{{Geo|$1|$2}}', $content);
  } 
  
   // specific script sv
  if($lang[$i] == "sv") {
    $content = str_ireplace(array('&quot;', '&lt;!--Latitud--&gt;', '&lt;!--Longitud--&gt;', "′", '″'), array('"', '', '', "'", '"'), $content);
    $content = preg_replace('/Lat(?: *)=(?: *)(.*)\n\|(?: *)Long(?: *)=(?: *)(.*)\n/i', '{{Geo|$1|$2}}', $content);
  }
  
  // specific script zh
  if($lang[$i] == "zh") {
    $content = str_ireplace(array('北纬', '东经' ), array('', ''), $content);
  }

  // change special strings (workaround)
  $content = str_ireplace(array("}}}}", "after.jpg"), array("}}", "after.xxx"), $content);

  // strip spaces, comments and nowiki
  $content = preg_replace(array('@\s*?=\s*@', '@\s*?:\s*@', '/(?:<!--(?:.|\s)*?-->)/', '/(?:<nowiki(?:.|\s)*?nowiki>)/' ), array('=', ':', 'C', 'N'), $content);

  // translate image tags
  $content = str_ireplace(array('Tiedosto:', 'Plik:', 'grafika=', 'imagem:', 'Ficheiro:', 'Изображение:', 'Afbeelding:', 'Fichier:', 'Bestand:', 'Fișier:', 'Fil:', 'Immagine=', 'Image:', 'קובץ:', 'תמונה=', 'imagen=', 'Tập tin:', 'Файл:', 'Bild:', 'widok=', 'File:', 'Datei:'), 'Image=', $content);

  // echo "<xmp>" . $content . "</xmp>"; // *** TEST ***

  preg_match_all("/(<title>(.*)<\/title>|<ns>(.*)<\/ns>|{{geo\s*?\|(.*)}}|{{geodata\s*?\|(.*)}})/i", $content, $matches);

  // print_r($matches); // *** TEST ***

  $rows = (count($matches,1) / count($matches,0)) - 1;
  $fp = fopen("./data/" . $lang[$i] . "-articles.js","wb+");
  fwrite($fp, "var addressPoints = [\n");
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
      if(!is_numeric($teile[0]) or !is_numeric($teile[1]) or abs($teile[0]) > 90 or abs($teile[1]) > 180) {
        error_log($lang[$i] . " - " . $matches[2][$m-2] . " = " . $teile[0] . " | " . $teile[1] . "\n", 3, "geodata.log");
      }
      else {
        fwrite($fp, '[' . number_format($teile[0],3) . ',' . number_format($teile[1],3) . ',"' . $matches[2][$m-2] . '",');
        // search for image
        $test = '<title>' . addcslashes($matches[2][$m-2], '()/') . '<.*?</page>';
        $pages = preg_match("@$test@is", $content, $page);
        if (!isset($page[0])) {
          $page[0] = substr($content, strpos($content, '<title>' . $matches[2][$m-2]), 1000);
        }       
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
        fwrite($fp, '"' . $image . '"' . "],\n");
      }
    }
  }
  fwrite($fp, "];\n");
  fclose($fp);
  copy("./data/" . $lang[$i] . "-articles.js","../w/data/" . $lang[$i] . "-articles.js");
}

error_log("\n==== " . date("Y-m-d - H:i:s") . " ====\n", 3, "geodata.log");

copy("geodata.log","../w/geodata.log");

echo '<span style="font-family:Courier New"</span>';
echo "<h2>GEODATA.LOG</h2>";
$errorfile = fopen("geodata.log","r");
while(!feof($errorfile)) {
  $line = fgets($errorfile);
  echo $line . "<br>";
  }
fclose($errorfile);

$delfile= fopen("articles.bz2", "w+");
fclose($delfile);

// echo '<pre>'; print_r($GLOBALS); echo '</pre>'; // *** TEST ***

?>
</body>
</html>
