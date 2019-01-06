var lang = "ru";
L.registerLocale(lang, mylocale);
L.setLocale(lang);

maptiles();

function onAll() {
    map.setView([jslat, jslon], jszoom, true);
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
    var jslayer = getUrlParameter('layer');
    if (!jslayer) {
        jslayer = 'W';
    }
    jslayer = jslayer.toUpperCase();
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
        var fmlat = e.latlng.lat.toFixed(5);
        var fmlng = e.latlng.lng.toFixed(5);
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

    function getMonumentImagePopupContent(name, imageUrl, imageThumb) {
        if (name === null) {
            name = '';
        }

        return $('<div>')
            .append(
                $('<a>')
                .attr('href', imageUrl)
                .attr('target', '_blank')
                .append(
                    $('<img>')
                        .attr('src', imageThumb)
                        .attr('width', '120')
                        .attr('onError', 'imgError(this);')
                )
            )
            .append($('<br>'))
            .append($('<span>').text(name))
            .append('&nbsp;')
            .append(
                $('<a>')
                    .attr('href', imageUrl)
                    .attr('target', '_blank')
                    .append(
                        $('<img>')
                            .attr('src', './lib/images/magnify-clip.png')
                            .attr('width', '15')
                            .attr('height', '11')
                            .attr('title', '⇱⇲')
                    )
            )
            .html();
    }

    function getMonumentTextPopupContent(name) {
        return $('<div>').text(name).html();
    }

    function bindPopup(leafletObject, monument) {
        var name = monument['name'];
        var imageThumb = monument['image-thumb-120px'];
        var imagePageUrl = monument['image-page-url'];

        var popupMinWidth;
        var popupMaxWidth;
        var popupContent;

        if (imagePageUrl && imageThumb) {
            popupMinWidth = 120;
            popupMaxWidth = 120;
            popupContent = getMonumentImagePopupContent(name, imagePageUrl, imageThumb);
        } else if (name) {
            popupMinWidth = 10;
            popupMaxWidth = 240;
            popupContent = getMonumentTextPopupContent(name);
        } else {
            // We don't have any info about the monument: do not add a popup
            return;
        }

        leafletObject.bindPopup(
            popupContent,
            {
                minWidth: popupMinWidth,
                maxWidth: popupMaxWidth
            }
        );
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

                bindPopup(marker, monument);

                marker.addTo(monuments);
            }

            $.each(monument['boundary-coordinates'], function (_, coordinates) {
                var polygon = L.polygon(coordinates, {weight: 1});

                bindPopup(polygon, monument);

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

    loadMapData(pageName, function (mapData) {
        lat = lat !== null ? lat : mapData['lat'];
        long = long !== null ? long : mapData['long'];
        zoom = zoom !== null ? zoom : mapData['zoom'];

        showMap(lat, long, zoom);
    });
}

initMonumentMap();