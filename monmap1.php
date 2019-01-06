<?php

// TODO skip comments when parsing templates
// TODO filter list of monument types - to display image
// TODO compose popup content using jquery

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
    <title>Wikivoyage Map</title>
    <link rel="icon" href="./lib/images/favicon.png" type= "image/png" />
    <link rel="stylesheet" href="./lib/leaflet/leaflet.css" />
    <link rel="stylesheet" href="./lib/poimap.css" />
  </head>
<body>
<div id="map">
  <div id="logo">
    <img src="./lib/images/logo.png" alt= "Logo" title= "Version 2016-07-13" width="64" height="64">
  </div>
  <script type="text/javascript" src="./lib/url-params.js"></script>
  <script type="text/javascript" src="./lib/jquery-3.3.1.min.js"></script>
  <script type="text/javascript" src="./lib/leaflet/leaflet.js"></script>
  <script type="text/javascript" src="./lib/buttons-new.js"></script>
  <script type="text/javascript" src="./lib/zoomdisplay.js"></script>
  <script type="text/javascript" src="./lib/i18n.js"></script>
  <script type="text/javascript" src="./locale/ru.js"></script>
  <script type="text/javascript" src="./lib/maptiles.js"></script>
<script type='text/javascript'>

var lang = "ru";
L.registerLocale(lang, mylocale);
L.setLocale(lang);

maptiles();

function onAll() {
  map.setView([jslat,jslon],jszoom,true);
  map.fitBounds(monuments.getBounds());
}

function showMap(lat, long, zoom) {
    var autozoom = "no";
    if (zoom === "auto") {
        autozoom = "yes";
    }
    if (parseInt(zoom) < 2 | parseInt(zoom) > 17 | isNaN(zoom) | lat === 0 | long === 0) {
        zoom = 14;
    }
    var jslayer = '<?php echo $_GET["layer"] ?: "W"; ?>'.toUpperCase();
    if (jslayer === "UNDEFINED") {
        jslayer = "WX";
    }
    if (jslayer === "OX") {
        jslayer = "WX";
    }

    // Make map
    var map = new L.Map('map', {center: new L.LatLng(lat, long), zoom: zoom, zoomControl: false});

    var popup = L.popup();

    function onMapClick(e) {
        var fmlat=e.latlng.lat.toFixed(5);
        var fmlng=e.latlng.lng.toFixed(5);
        popup
            .setLatLng(e.latlng)
            .setContent(L._('You clicked the map at') + ' <br> lat=' + fmlat + ' | long=' + fmlng)
            .openOn(map);
    }

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
        image.src = image.src.replace("wikipedia/commons", "wikivoyage/ru");
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
        var redIcon = L.icon({iconUrl: './ico24/target.png', iconSize: [32, 32], iconAnchor: [16, 16]});
        L.marker([lat, long], {icon: redIcon}).addTo(monuments);
    }

    if (autozoom == "yes") {
        map.fitBounds(monuments.getBounds());
        lat = map.getCenter(monuments).lat.toFixed(5);
        long = map.getCenter(monuments).lng.toFixed(5);
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
    var warning = 'url(' + imgpath + 'line.png) "' + L._('Content with {external} is hosted externally, so enabling it shares your data with other sites.', {external: ' "url(' + imgpath + 'external.png)" '}) + '"';
    document.styleSheets[1].cssRules[4].style.content = warning;

    $.ajax({
        url: 'api.php',
        data: $.param({
            query: 'get-page-data',
            page: getUrlParameter('name'),
            items: 'map-data,monuments',
            fields: 'name,type,lat,long,image-thumb-120px,image-page-url,boundary-coordinates',
            filter: 'able-to-display-on-map'
        })
    }).done(function (result) {
        $.each(result.data.monuments, function (_, monument) {
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
}

function loadMapData(pageName, onSuccess) {
    $.ajax({
        url: 'api.php',
        data: $.param({
            query: 'get-page-data',
            page: pageName,
            items: 'map-data',
        })
    }).done(function (result) {
        var mapData = result.data['map-data'];
        onSuccess(mapData);
    });
}

function initMonumentMap() {
    var pageName = getUrlParameter('name');

    var lat = getUrlParameter('lat');
    var long = getUrlParameter('long');
    var zoom = getUrlParameter('zoom');

    document.title = pageName + ' - Wikivoyage Map';

    loadMapData(pageName, function(mapData) {
        lat = lat !== null ? lat : mapData['lat'];
        long = long !== null ? long : mapData['long'];
        zoom = zoom !== null ? zoom : mapData['zoom'];

        showMap(lat, long, zoom);
    });
}

initMonumentMap();

</script>
 
</div>
</body>
</html>
