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
  <script type="text/javascript" src="./lib/jquery-3.3.1.min.js"></script>
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

$templateReader = new TemplateReader();

$monumentTitleParams = new MonumentTitleMapParams($content);

$mapLat = ArrayUtils::getFirstNotNullValue([$requestParameters->getLat(), $monumentTitleParams->getLat()], 0);
$mapLong = ArrayUtils::getFirstNotNullValue([$requestParameters->getLon(), $monumentTitleParams->getLon()], 0);
$mapZoom = ArrayUtils::getFirstNotNullValue([$requestParameters->getZoom(), $monumentTitleParams->getZoom()], 14);

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

map.addLayer(monuments);

function imagePopupContent(name, imageUrl, imageThumb) {
    if (name === null) {
        name = '';
    }

    return '<a href = ' + imageUrl + '><img src="' + imageThumb + '" width="120" onerror="imgError(this);"></a><br />' + name + '&nbsp;<a href = ' + imageUrl + '><img src="./lib/images/magnify-clip.png" widht="15" height="11" title="⇱⇲">';
}

function getPopupContent(name, imageUrl, imageThumb) {
    if (imageUrl !== null && imageThumb !== null) {
        return imagePopupContent(name, imageUrl, imageThumb);
    } else if (name !== null) {
        return name;
    } else {
        return null;
    }
}

function bindPopup(leafletObject, name, imageUrl, imageThumb) {
    var popupContent = getPopupContent(name, imageUrl, imageThumb);
    if (popupContent !== null) {
        var popupMinWidth = 10;
        var popupMaxWidth = 240;

        if (imageThumb !== null && imageUrl !== null) {
            popupMinWidth = 120;
            popupMaxWidth = 120;
        }

        var popupProps = {minWidth: popupMinWidth, maxWidth: popupMaxWidth};

        leafletObject.bindPopup(popupContent, popupProps);
    }
}

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

  $.ajax({
      url: 'api.php',
      data: $.param({
          query: 'get-page-data',
          page: <?php echo json_encode($requestParameters->getName()) ?>,
          items: 'map-data,monuments',
          fields: 'name,type,lat,long,image-thumb-120px,image-page-url,boundary-coordinates',
          filter: 'able-to-display-on-map'
      })
  }).done(function(result) {
      $.each(result.data.monuments, function(_, monument) {
          var name = monument.name;

          if (monument['lat'] && monument['long']) {
              var monumentType = monument['type'];
              if (!monumentType) {
                  monumentType = 'other';
              }

              var markerCoordinates = [monument['lat'], monument['long']];
              var markerIcon = L.icon({
                iconUrl: "./ico24/" + "mon-" + monumentType + ".png",
                iconAnchor: [12, 12],
                popupAnchor: [0, -12]
              });

              var markerProps = {
                title: monument['name'],
                icon: markerIcon
              };
              var marker = L.marker(markerCoordinates, markerProps);

              bindPopup(marker, name, monument['image-page-url'], monument['image-thumb-120px']);

              marker.addTo(monuments);
          }

          $.each(monument['boundary-coordinates'], function (_, coordinates) {
              var polygon = L.polygon(coordinates, {weight: 1});

              bindPopup(polygon, name, monument['image-page-url'], monument['image-thumb-120px']);

              polygon.addTo(map);
          });
      });
  });

</script>
 
</div>
</body>
</html>
