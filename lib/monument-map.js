var lang = "ru";
L.registerLocale(lang, mylocale);
L.setLocale(lang);

maptiles();

function onAll() {
    map.setView([jslat, jslon], jszoom, true);
    map.fitBounds(monuments.getBounds());
}

// load local image
function imgError(image) {
    image.onerror = "";
    image.src = image.src.replace("wikipedia/commons", "wikivoyage/ru");
    return true;
}

function getMonumentImagePopupContent(name, uploadLink, imageUrl, imageThumb) {
    if (name === null) {
        name = '';
    }

    var result = $('<div>')
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
        );

    if (uploadLink) {
        result.append(getUploadLinkElement(uploadLink))
    }

    return result.html();
}

function getMonumentTextPopupContent(name, uploadLink) {
    var result = $('<div>')
        .append(
            $('<div>')
                .text(name)
        );
    if (uploadLink) {
        result.append(getUploadLinkElement(uploadLink));
    }
    return result.html();
}

function getUploadLinkElement(uploadLink) {
    return $('<div>')
        .append(
            $('<a>')
                .attr('href', uploadLink)
                .attr('target', '_blank')
                .text('Загрузить фото')
        );
}

function bindPopup(leafletObject, monument) {
    var name = monument['name'];
    var uploadLink = monument['upload-link'];
    var imageThumb = monument['image-thumb-120px'];
    var imagePageUrl = monument['image-page-url'];

    var popupMinWidth;
    var popupMaxWidth;
    var popupContent;

    if (imagePageUrl && imageThumb) {
        popupMinWidth = 120;
        popupMaxWidth = 120;
        popupContent = getMonumentImagePopupContent(name, uploadLink, imagePageUrl, imageThumb);
    } else if (name) {
        popupMinWidth = 10;
        popupMaxWidth = 240;
        popupContent = getMonumentTextPopupContent(name, uploadLink);
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

function addMarkersToMap(map, monuments)
{
    $.each(monuments, function (_, monument) {
        if (monument['lat'] && monument['long']) {
            try {
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

                marker.addTo(map);
            } catch (e) {
                window.console.error(e);
            }
        }
    });
}

function addBoundariesToMap(map, monuments)
{
    $.each(monuments, function (_, monument) {
        $.each(monument['boundary-coordinates'], function (_, coordinates) {
            var polygon = L.polygon(coordinates, {weight: 1});

            bindPopup(polygon, monument);

            polygon.addTo(map);
        });
    });
}

function loadBoundaries(pageName, onSuccess)
{
    $.ajax({
        url: 'api.php',
        data: $.param({
            query: 'get-page-data',
            page: pageName,
            items: 'map-data,monuments',
            fields: 'name,image-thumb-120px,image-page-url,upload-link,boundary-coordinates',
            filter: 'able-to-display-on-map'
        })
    }).done(function (result) {
        onSuccess(result.data.monuments);
    });
}

function loadMonuments(pageName, onSuccess)
{
    $.ajax({
        url: 'api.php',
        data: $.param({
            query: 'get-page-data',
            page: pageName,
            items: 'monuments',
            fields: 'name,type,lat,long,image-thumb-120px,image-page-url,upload-link',
            filter: 'able-to-display-on-map'
        })
    }).done(function (result) {
        onSuccess(result.data.monuments);
    });
}

function loadMapDataWithMonuments(pageName, onSuccess) {
    $.ajax({
        url: 'api.php',
        data: $.param({
            query: 'get-page-data',
            page: pageName,
            items: 'map-data,monuments',
            fields: 'name,type,lat,long,image-thumb-120px,image-page-url,upload-link',
            filter: 'able-to-display-on-map'
        })
    }).done(function (result) {
        var mapData = result.data['map-data'];
        var monuments = result.data['monuments'];
        onSuccess(mapData, monuments);
    });
}

function loadPageTitlesPrefix(prefix, onSuccess) {
    $.ajax({
        url: 'api.php',
        data: $.param({
            query: 'list-pages',
            prefix: prefix,
        })
    }).done(function (result) {
        onSuccess(result.data.map(function(page) {
            return page.title;
        }));
    });
}


function loadPageTitlesPrefixParts(prefixParts, onSuccess) {
    $.ajax({
        url: 'api.php',
        data: $.param({
            query: 'list-pages',
            'prefix-parts': prefixParts,
        })
    }).done(function (result) {
        onSuccess(result.data.map(function(page) {
            return page.title;
        }));
    });
}

function initializeMap(lat, long, zoom) {
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

    // Layer monuments
    // var monumentsLayer = new L.featureGroup();
    var monumentsLayer = L.markerClusterGroup();


    if (jslayer.indexOf("X") != -1) {
        var redIcon = L.icon({iconUrl: './ico24/target.png', iconSize: [32, 32], iconAnchor: [16, 16]});
        L.marker([lat, long], {icon: redIcon}).addTo(monumentsLayer);
    }

    if (autozoom == "yes") {
        map.fitBounds(monumentsLayer.getBounds());
        lat = map.getCenter(monumentsLayer).lat.toFixed(5);
        long = map.getCenter(monumentsLayer).lng.toFixed(5);
    }

    // Controls
    var basemaps = {};
    var overlays = {};

    basemaps[L._('Wikimedia') + ' <img src="./lib/images/wmf-logo-12.png" />'] = wikimedia;
    basemaps[L._('Mapnik') + ' <img src="./lib/images/external.png" />'] = mapnik;
    basemaps[L._('Relief_map') + ' <img src="./lib/images/external.png" />'] = landscape;
    overlays[L._('Monuments') + ' <img src="./lib/images/wv-logo-12.png" />'] = monumentsLayer;

    map.addControl(new L.Control.Layers(basemaps, overlays));
    map.addControl(new L.Control.Scale());
    map.addControl(new L.Control.Buttons());

    // External content warning
    var imgpath = '../lib/images/';
    if (L.Browser.ie) {
        imgpath = './lib/images/';
    }
    var warning = 'url(' + imgpath + 'line.png) "' + L._('Content with {external} is hosted externally, so enabling it shares your data with other sites.', {external: ' "url(' + imgpath + 'external.png)" '}) + '"';
    document.styleSheets[3].cssRules[4].style.content = warning;

    return {
        map: map,
        monumentsLayer: monumentsLayer,
        finalizeMonumentsLayer: function() {
            this.map.addLayer(this.monumentsLayer);
        }
    }
}

function initSinglePage(pageName)
{
    var lat = getUrlParameter('lat');
    var long = getUrlParameter('long');
    var zoom = getUrlParameter('zoom');
    var hideboundaries = getBooleanUrlParameter('hide-boundaries');
    var hidemarkers = getBooleanUrlParameter('hide-markers');

    document.title = pageName + ' - Wikivoyage Map';

    loadMapDataWithMonuments(pageName, function (mapData, monuments) {
        lat = lat !== null ? lat : mapData['lat'];
        long = long !== null ? long : mapData['long'];
        zoom = zoom !== null ? zoom : mapData['zoom'];

        var mapObject = initializeMap(lat, long, zoom);

        if (!hidemarkers) {
            addMarkersToMap(mapObject.monumentsLayer, monuments);
            mapObject.finalizeMonumentsLayer();
        }

        if (!hideboundaries) {
            loadBoundaries(pageName, function (monuments) {
                addBoundariesToMap(mapObject.map, monuments);
            });
        }
    });
}

function initMultiplePages(pageNames)
{
    var lat = getUrlParameter('lat');
    var long = getUrlParameter('long');
    var zoom = getUrlParameter('zoom');
    var sequential = getBooleanUrlParameter('sequential');
    var hideboundaries = getBooleanUrlParameter('hide-boundaries');
    var hidemarkers = getBooleanUrlParameter('hide-markers');

    var executionFunction = sequential ? runSequence : runAsync;

    var mapObject = initializeMap(lat, long, zoom);

    var loadMarkersFunctions = [];
    var loadBoundariesFunctions = [];

    pageNames.forEach(function(pageName) {
        if (!hidemarkers) {
            loadMarkersFunctions.push(
                function (onSuccess) {
                    loadMonuments(pageName, function (monuments) {
                        onSuccess(monuments);
                    });
                }
            );
        }
        if (!hideboundaries) {
            loadBoundariesFunctions.push(
                function (onSuccess) {
                    loadBoundaries(pageName, function (monuments) {
                        addBoundariesToMap(mapObject.map, monuments);
                        onSuccess();
                    });
                }
            );
        }
    });

    function loadAllMarkers(onSuccess) {
        executionFunction(loadMarkersFunctions, function(monumentChunks) {
            monumentChunks.forEach(function(monuments) {
                addMarkersToMap(mapObject.monumentsLayer, monuments);
            });
            mapObject.finalizeMonumentsLayer();
            onSuccess();
        });
    }


    function loadAllBoundaries(onSuccess) {
        executionFunction(loadBoundariesFunctions, function() {
            onSuccess();
        });
    }

    var loadFunctions = [
        loadAllMarkers,
        loadAllBoundaries
    ];

    executionFunction(loadFunctions, function() {});
}

function initPages(pageNames) {
    if (pageNames.length === 1) {
        initSinglePage(pageNames[0]);
    } else {
        initMultiplePages(pageNames);
    }
}

function initMonumentMap() {
    var pageNamesRaw = getUrlParameter('name');
    if (pageNamesRaw) {
        var pageNames = pageNamesRaw.split(',');
        initPages(pageNames);
    } else {
        var namePrefix = getUrlParameter('name-prefix');
        if (namePrefix) {
            loadPageTitlesPrefix(namePrefix, function(pageNames) {
                initPages(pageNames);
            });
        } else {
            var namePrefixParts = getUrlParameter('name-prefix-parts');
            if (namePrefixParts) {
                loadPageTitlesPrefixParts(namePrefixParts, function(pageNames) {
                    initPages(pageNames);
                })
            }
        }
    }
}

initMonumentMap();