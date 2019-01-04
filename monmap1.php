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

class WikimediaMapsReader
{
    /**
     * @param string[] $wikidataIds
     * @return array|null
     */
    public function getPolygonsForWikidataIds($wikidataIds)
    {
        $result = null;

        if (count($wikidataIds) > 0) {
            $url = 'https://maps.wikimedia.org/geoshape?getgeojson=1&ids=' . implode(',', $wikidataIds);
            $geoJsonStr = file_get_contents($url);
            $geoJsonData = json_decode($geoJsonStr, true);

            foreach ($geoJsonData['features'] as $item) {
                $wdid = $item['id'];
                $polygons = $item['geometry']['coordinates'];

                if ($item['geometry']['type'] === 'MultiPolygon') {
                    $result[$wdid] = [];
                    foreach ($polygons as $polygon1) {
                        foreach ($polygon1 as $polygon2) {
                            $result[$wdid][] = GeoUtils::swapLatLong($polygon2);
                        }
                    }
                }
            }
        }

        return $result;
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
        return isset($_GET['lat']) ? (float)$_GET['lat'] : null;
    }

    public function getLon()
    {
        return isset($_GET['lon']) ? (float)$_GET['lon'] : null;
    }

    public function getLayer()
    {
        return $_GET['layer'];
    }

    public function getZoom()
    {
        return isset($_GET["zoom"]) ? (int)$_GET["zoom"] : null;
    }
}

class TemplateReader
{
    public function read($templateNames, $wikitext)
    {
        if (empty($templateNames)) {
            throw new Exception('Template names list is empty');
        }

        $listingsData = [];

        foreach ($this->getTemplateWikitexts($templateNames, $wikitext) as $templateWikitext) {
            $listingsData[] = $this->parseTemplateWikitext($templateWikitext);
        }

        return $listingsData;
    }

    private function getTemplateWikitexts($templateNames, $wikitext)
    {
        $templateWikitexts = [];

        $templateNamesStr = implode(
            '|',
            array_map(function($templateName) {
                return preg_quote(strtolower($templateName), '/');
            }, $templateNames)
        );

        $matchResult = preg_match_all(
            '/\\{\\{\s*(' . $templateNamesStr . ')(\s|\\|)/i',
            $wikitext,
            $matches,
            PREG_OFFSET_CAPTURE
        );
        if ($matchResult) {
            foreach ($matches[1] as $match) {
                $position = $match[1];

                $braceCount = 2;
                $endIndex = null;

                for ($i = $position; $i < strlen($wikitext); $i++) {
                    $char = $wikitext[$i];
                    if ($char === '{') {
                        $braceCount++;
                    } elseif ($char === '}') {
                        $braceCount--;
                    }

                    if ($braceCount === 0) {
                        $endIndex = $i;
                        break;
                    }
                }

                if (!is_null($endIndex)) {
                    $templateWikitexts[] = substr($wikitext, $position, $endIndex - $position - 1);
                }
            }
        }

        return $templateWikitexts;
    }

    private function parseTemplateWikitext($wikitext)
    {
        $templateData = [];

        $templateItems = explode('|', $wikitext);
        array_shift($templateItems); // throw away template name itself

        foreach ($templateItems as $templateItem) {
            $items = explode('=', $templateItem, 2);
            $key = $items[0];
            $value = isset($items[1]) ? $items[1] : '';

            $key = trim($key);
            $value = trim($value);

            $templateData[$key] = $value;
        }

        return $templateData;
    }
}

class GeoUtils
{
    public static function swapLatLong($data)
    {
        return array_map(function($item) {
            return [$item[1], $item[0]];
        }, $data);
    }
}

class BoundaryPageReader
{
    public function read($pageName)
    {
        $wikivoyagePageReader = new WikivoyagePageReader();
        $pageContents = $wikivoyagePageReader->read($pageName);
        $pageContents = $this->stripNoinclude($pageContents);
        $data = json_decode($pageContents, true);
        return GeoUtils::swapLatLong($data);
    }

    private function stripNoinclude($pageContents)
    {
        return preg_replace(
            '#' . preg_quote('<noinclude>') . '.*?' . preg_quote('</noinclude>') . '#ms',
            '',
            $pageContents
        );
    }
}

class ArrayUtils
{
    public static function getFirstNotNullValue($values, $defaultValue)
    {
        foreach ($values as $value) {
            if (!is_null($value)) {
                return $value;
            }
        }

        return $defaultValue;
    }

    public static function getNonEmptyStringValue($array, $key)
    {
        if (isset($array[$key])) {
            $value = (string)$array[$key];
            if ($value !== '') {
                return $value;
            } else {
                return null;
            }
        } else {
            return null;
        }
    }
}

function getImageStorageUrl($image) {
    $image = str_replace(' ', '_', $image);

    if (substr($image,1,1) === '/') {
        return $image;
    }

    $md5 = md5($image);
    return substr($md5,0,1) . "/" . substr($md5,0,2) . "/" . $image;
}

$wikivoyagePageReader = new WikivoyagePageReader();
$wikimediaMapsReader = new WikimediaMapsReader();
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

class MonumentTitleMapParams
{
    private $lat;

    private $lon;

    private $zoom;

    public function __construct($wikitext)
    {
        $this->lat = null;
        $this->lon = null;
        $this->zoom = null;

        $templateReader = new TemplateReader();
        $monumentTitleDatas = $templateReader->read(['monument-title', 'monument-title/nature'], $wikitext);

        if (isset($monumentTitleDatas[0])) {
            $firstMonumentTitleData = $monumentTitleDatas[0];
            $this->lat = (float)$firstMonumentTitleData['lat'];
            $this->lon = (float)$firstMonumentTitleData['long'];
            $this->zoom = (int)$firstMonumentTitleData['zoom'];
        }
    }

    public function getLat()
    {
        return $this->lat;
    }

    public function getLon()
    {
        return $this->lon;
    }

    public function getZoom()
    {
        return $this->zoom;
    }
}

$file = str_replace("\'","'", $requestParameters->getName());
$content = $wikivoyagePageReader->read($file);

$boundaries = [];

$templateReader = new TemplateReader();
$boundaryPageReader = new BoundaryPageReader();

$monuments = $templateReader->read(['monument', 'natural monument'], $content);

foreach ($monuments as $monument) {
    if (isset($monument['boundary-page'])) {
        $boundaryData = $boundaryPageReader->read($monument['boundary-page']);
        if ($boundaryData) {
            $name = ArrayUtils::getNonEmptyStringValue($monument, 'name');
            $image = ArrayUtils::getNonEmptyStringValue($monument, 'image');
            if (!is_null($image)) {
                $image = getImageStorageUrl($image);
            }

            $boundaries[] = [
                'name' => $name,
                'image' => $image,
                'coordinates' => $boundaryData,
            ];
        }
    }
}

$wikidataIds = [];
foreach ($monuments as $monument) {
    if (isset($monument['wdid'])) {
        $wikidataIds[] = $monument['wdid'];
    }
}

$monumentsByWikidataIds = [];
foreach ($monuments as $monument) {
    if (isset($monument['wdid']) && $monument['wdid'] !== '') {
        $wdid = $monument['wdid'];
        if (!isset($monumentsByWikidataIds[$wdid])) {
            $monumentsByWikidataIds[$wdid] = [];
        }
        $monumentsByWikidataIds[$wdid][] = $monument;
    }
}

$wikidataBoundaries = $wikimediaMapsReader->getPolygonsForWikidataIds($wikidataIds);
foreach ($wikidataBoundaries as $wdid => $boundaryInfo) {
    foreach ($monumentsByWikidataIds[$wdid] as $monument) {
        $name = ArrayUtils::getNonEmptyStringValue($monument, 'name');
        $image = ArrayUtils::getNonEmptyStringValue($monument, 'image');
        if (!is_null($image)) {
            $image = getImageStorageUrl($image);
        }

        foreach ($boundaryInfo as $boundaryData) {
            $boundaries[] = [
                'name' => $name,
                'image' => $image,
                'coordinates' => $boundaryData,
            ];
        }
    }
}


$monumentTitleParams = new MonumentTitleMapParams($content);

$mapLat = ArrayUtils::getFirstNotNullValue([$requestParameters->getLat(), $monumentTitleParams->getLat()], 0);
$mapLong = ArrayUtils::getFirstNotNullValue([$requestParameters->getLon(), $monumentTitleParams->getLon()], 0);
$mapZoom = ArrayUtils::getFirstNotNullValue([$requestParameters->getZoom(), $monumentTitleParams->getZoom()], 14);

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
  $name = $type = $lat = $long = $image = $wdid = '';
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

  if (!is_null($wdid)) {
      $wdid = trim($wdid);
      if ($wdid !== '') {
          $wikidataIds[] = $wdid;
      }
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
var jslat = <?php echo json_encode($mapLat); ?>;
var jslon = <?php echo json_encode($mapLong); ?>;
var jszoom = <?php echo json_encode($mapZoom); ?>;
var boundaries = <?php echo json_encode($boundaries); ?>;
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
var popup = L.popup();

map.on('click', onMapClick);

var mapLayer = wikimedia;

if (jslayer.indexOf("M") !== -1) {
    mapLayer = mapnik;
} else if (jslayer.indexOf("R") !== -1) {
    mapLayer = landscape;
}

map.addLayer(mapLayer);

  // load local image
  function imgError(image) {   
    image.onerror = "";
    image.src = image.src.replace("wikipedia/commons","wikivoyage/ru");
    return true;
  } 

// Layer monuments
var monuments = new L.featureGroup();
var markerIndex = 1;

while(markerIndex < jsmax){
  if (jsx[markerIndex] != "0"){
    var tooltip = jsn[markerIndex].replace('<br />','');
    var imageUrl = '"https://ru.m.wikivoyage.org/wiki/File:' + jsf[markerIndex].substr(5) + '"';

    // no image
    if (jsf[markerIndex] === "0/01/no"){
      var popupContent = jsn[markerIndex];
      var popupMinWidth = 10;
      var popupMaxWidth = 240;
    }
    // with image
    else {
      var popupContent = '<a href = ' + imageUrl + '><img src="https://upload.wikimedia.org/wikipedia/commons/thumb/' + jsf[markerIndex] + '/120px-' + jsf[markerIndex].substr(5) + '" width="120" onerror="imgError(this);"></a><br />' + jsn[markerIndex] + '&nbsp;<a href = ' + imageUrl + '><img src="./lib/images/magnify-clip.png" widht="15" height="11" title="⇱⇲">';
      var popupMinWidth = 120;
      var popupMaxWidth = 120;
    }

    var zIndexOffset = 1000 - (markerIndex * 2);
    var markerIcon = L.icon({
        iconUrl: "./ico24/" + "mon-" + jsc[markerIndex] + ".png",
        iconAnchor: [12, 12],
        popupAnchor: [0, -12]
    });

    var markerCoordinates = [
        jsx[markerIndex],
        jsy[markerIndex]
    ];
    var markerProps = {
        title: tooltip,
        zIndexOffset: zIndexOffset,
        icon: markerIcon
    };
    var marker = L.marker(markerCoordinates, markerProps);
    var popupProps = {minWidth: popupMinWidth, maxWidth: popupMaxWidth};
    marker.bindPopup(popupContent, popupProps);
    marker.addTo(monuments);
  }

  markerIndex++;
}

map.addLayer(monuments);

function imagePopupContent(name, image) {
    if (name === null) {
        name = '';
    }

    var imageUrl = '"https://ru.m.wikivoyage.org/wiki/File:' + image.substr(5) + '"';
    return '<a href = ' + imageUrl + '><img src="https://upload.wikimedia.org/wikipedia/commons/thumb/' + image + '/120px-' + image.substr(5) + '" width="120" onerror="imgError(this);"></a><br />' + name + '&nbsp;<a href = ' + imageUrl + '><img src="./lib/images/magnify-clip.png" widht="15" height="11" title="⇱⇲">';
}

function getPopupContent(name, image) {
    if (image !== null) {
        return imagePopupContent(name, image);
    } else if (name !== null) {
        return name;
    } else {
        return null;
    }
}

function bindPopup(leafletObject, name, image) {
    var popupContent = getPopupContent(name, image);
    if (popupContent !== null) {
        var popupMinWidth = 10;
        var popupMaxWidth = 240;

        if (image !== null) {
            popupMinWidth = 120;
            popupMaxWidth = 120;
        }

        var popupProps = {minWidth: popupMinWidth, maxWidth: popupMaxWidth};

        leafletObject.bindPopup(popupContent, popupProps);
    }
}

boundaries.forEach(function(boundary) {
    var coordinates = boundary['coordinates'];
    var name = boundary['name'];
    var image = boundary['image'];
    var polygon = L.polygon(coordinates);

    bindPopup(polygon, name, image);

    polygon.addTo(map);
});

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
