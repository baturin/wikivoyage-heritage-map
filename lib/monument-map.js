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

function getMonumentImagePopupContent(monument) {
    var name = monument[MonumentFields.NAME];
    var address = monument[MonumentFields.ADDRESS];
    var uploadLink = monument[MonumentFields.UPLOAD_LINK];
    var listingUrl = monument[MonumentFields.LISTING_URL];
    var imageThumb = monument[MonumentFields.IMAGE_THUMB_120PX];
    var imagePageUrl = monument[MonumentFields.IMAGE_PAGE_URL];

    if (name === null) {
        name = '';
    }

    var imageElement = $('<img>')
        .attr('src', imageThumb)
        .attr('width', '120')
        .attr('onError', 'imgError(this);');
    var imageLinkElement = $('<a>')
        .attr('href', imagePageUrl)
        .attr('target', '_blank')
        .append(
            imageElement
        );
    var popupContentsElement = $('<div>');

    popupContentsElement.append(imageLinkElement);

    popupContentsElement.append($('<br>'));
    popupContentsElement.append($('<span>').text(name));

    if (address) {
        popupContentsElement.append($('<br>'));
        popupContentsElement.append($('<span>').css('font-style', 'italic').text(address));
    }

    if (uploadLink) {
        popupContentsElement.append(getUploadLinkElement(uploadLink))
    }

    if (listingUrl) {
        popupContentsElement.append(getListingLinkElement(listingUrl));
    }

    return popupContentsElement.html();
}

function getMonumentTextPopupContent(monument) {
    var name = monument[MonumentFields.NAME];
    var address = monument[MonumentFields.ADDRESS];
    var uploadLink = monument[MonumentFields.UPLOAD_LINK];
    var listingUrl = monument[MonumentFields.LISTING_URL];

    var popupContentsElement = $('<div>');
    if (name) {
        popupContentsElement.append($('<div>').text(name));
    }
    if (address) {
        popupContentsElement.append($('<div>').css('font-style', 'italic').text(address))
    }
    if (uploadLink) {
        popupContentsElement.append(getUploadLinkElement(uploadLink));
    }
    if (listingUrl) {
        popupContentsElement.append(getListingLinkElement(listingUrl));
    }
    return popupContentsElement.html();
}

function getUploadLinkElement(uploadLink) {
    return getActionLinkElement(
        uploadLink,
        'Загрузить фото',
        'ico24/upload.svg'
    );
}

function getListingLinkElement(listingUrl) {
    return getActionLinkElement(
        listingUrl,
        'Смотреть в списке',
        'ico24/view-listing.svg'
    );
}

function getActionLinkElement(url, text, image) {
    return $('<div>')
        .append(
            $('<a class="action-link">')
                .attr('href', url)
                .attr('target', '_blank')
                .append(
                    $('<img class="action-image">')
                        .attr('src', image)
                        .attr('height', '16px')
                        .attr('width', '16px')
                )
                .append(
                    $('<div class="action-text">')
                        .text(text)
                )
        );
}

function bindPopup(leafletObject, monument) {
    var name = monument[MonumentFields.NAME];
    var address = monument[MonumentFields.ADDRESS];
    var imageThumb = monument[MonumentFields.IMAGE_THUMB_120PX];
    var imagePageUrl = monument[MonumentFields.IMAGE_PAGE_URL];
    var uploadLink = monument[MonumentFields.UPLOAD_LINK];

    var popupMinWidth;
    var popupMaxWidth;
    var popupContent;

    if (imagePageUrl && imageThumb) {
        popupMinWidth = 120;
        popupMaxWidth = 120;
        popupContent = getMonumentImagePopupContent(monument);
    } else if (name || address || uploadLink) {
        popupMinWidth = 10;
        popupMaxWidth = 240;
        popupContent = getMonumentTextPopupContent(monument);
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

function addMarkerToMap(map, monument, lat, long)
{
    var monumentType = monument[MonumentFields.TYPE];
    if (!monumentType) {
        monumentType = 'other';
    }

    var markerCoordinates = [lat, long];
    var markerIcon = L.icon({
        iconUrl: "./ico24/" + "monument/" + monumentType.replace(' ', '-') + ".png",
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
}

function addMarkersToMap(map, monuments)
{
    $.each(monuments, function (_, monument) {
        var lat = monument[MonumentFields.LAT];
        var long = monument[MonumentFields.LONG];

        if (!lat || !long) {
            return;
        }

        try {
            if (isHighlightedMonument(monument)) {
                addHighlightMarker(lat, long, map);
            }

            addMarkerToMap(map, monument, lat, long);
        } catch (e) {
            window.console.error(e);
        }
    });
}

var MonumentFields = {
    KNID: 'knid',
    NAME: 'name',
    TYPE: 'type',
    LAT: 'lat',
    LONG: 'long',
    ADDRESS: 'address',
    IMAGE_THUMB_120PX: 'image-thumb-120px',
    IMAGE_PAGE_URL: 'image-page-url',
    UPLOAD_LINK: 'upload-link',
    LISTING_URL: 'listing-url',
    BOUNDARY_COORDINATES: 'boundary-coordinates',
};

function composeFieldsRequest(monumentFields)
{
    return monumentFields.join(",");
}

var PolygonStyles = {
    NORMAL: {
        weight: 1
    },
    HIGHLIGHT: {
        weight: 2,
        fillColor: '#ff0000',
        color: '#ff0000',
    }
};

function isHighlightedMonument(monument)
{
    var highlightKnid = getUrlParameter('highlight-knid');
    return (
        highlightKnid &&
        monument[MonumentFields.KNID] === highlightKnid
    );
}

function addBoundariesToMap(map, monuments)
{
    $.each(monuments, function (_, monument) {
        $.each(monument['boundary-coordinates'], function (_, coordinates) {
            var style = isHighlightedMonument(monument) ? PolygonStyles.HIGHLIGHT : PolygonStyles.NORMAL;
            var polygon = L.polygon(coordinates, style);

            bindPopup(polygon, monument);

            polygon.addTo(map);
        });
    });
}

function loadBoundaries(pageName, onSuccess, loadAddress)
{
    var fields = [
        MonumentFields.KNID,
        MonumentFields.NAME,
        MonumentFields.TYPE,
        MonumentFields.IMAGE_THUMB_120PX,
        MonumentFields.IMAGE_PAGE_URL,
        MonumentFields.UPLOAD_LINK,
        MonumentFields.BOUNDARY_COORDINATES
    ];

    if (loadAddress) {
        fields.push(MonumentFields.ADDRESS);
    }

    $.ajax({
        url: 'api.php',
        data: $.param({
            query: 'get-page-data',
            page: pageName,
            items: 'map-data,monuments',
            fields: composeFieldsRequest(fields),
            filter: 'able-to-display-on-map'
        })
    }).done(function (result) {
        onSuccess(result.data.monuments);
    });
}

function loadMonuments(pageName, onSuccess, loadAddress)
{
    var fields = [
        MonumentFields.KNID,
        MonumentFields.NAME,
        MonumentFields.TYPE,
        MonumentFields.LAT,
        MonumentFields.LONG,
        MonumentFields.IMAGE_THUMB_120PX,
        MonumentFields.IMAGE_PAGE_URL,
        MonumentFields.UPLOAD_LINK,
        MonumentFields.LISTING_URL,
    ];

    if (loadAddress) {
        fields.push(MonumentFields.ADDRESS);
    }

    $.ajax({
        url: 'api.php',
        data: $.param({
            query: 'get-page-data',
            page: pageName,
            items: 'monuments',
            fields: composeFieldsRequest(fields),
            filter: 'marker-enabled,able-to-display-on-map'
        })
    }).done(function (result) {
        onSuccess(result.data.monuments);
    });
}

function loadMapDataWithMonuments(pageName, onSuccess, loadAddress) {
    var fields = [
        MonumentFields.KNID,
        MonumentFields.NAME,
        MonumentFields.TYPE,
        MonumentFields.LAT,
        MonumentFields.LONG,
        MonumentFields.IMAGE_THUMB_120PX,
        MonumentFields.IMAGE_PAGE_URL,
        MonumentFields.UPLOAD_LINK,
        MonumentFields.LISTING_URL,
    ];

    if (loadAddress) {
        fields.push(MonumentFields.ADDRESS);
    }

    $.ajax({
        url: 'api.php',
        data: $.param({
            query: 'get-page-data',
            page: pageName,
            items: 'map-data,monuments',
            fields: composeFieldsRequest(fields),
            filter: 'marker-enabled,able-to-display-on-map'
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

function loadDistrictBoundaries(districtid, onSuccess) {
    $.ajax({
        url: 'api.php',
        data: $.param({
            query: 'get-wikidata-boundaries',
            'wikidata-id': districtid
        })
    }).done(function(result) {
        onSuccess(result.data['boundary-coordinates']);
    })
}

function addHighlightMarker(lat, long, monumentsLayer)
{
    var redIcon = L.icon({
        iconUrl: './ico24/target.png',
        iconSize: [32, 32],
        iconAnchor: [16, 16]
    });
    L.marker([lat, long], {icon: redIcon}).addTo(monumentsLayer);
}

function initializeMap(lat, long, zoom, districtid, hideDistrictBoundaries, disableAutoFit) {
    var autofit = false;

    if (zoom === null && lat === null && long === null && !disableAutoFit) {
        autofit = true;
    }

    if (parseInt(zoom) < 2 || parseInt(zoom) > 17 || isNaN(zoom) || zoom === null || zoom === undefined || lat === 0 || long === 0) {
        zoom = 2;
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
    var noGroupMarkers = getBooleanUrlParameter('no-group-markers');

    var monumentsLayer = null;
    if (noGroupMarkers) {
        monumentsLayer = new L.featureGroup();
    } else {
        monumentsLayer = L.markerClusterGroup();
    }


    if (jslayer.indexOf("X") !== -1) {
        addHighlightMarker(lat, long, monumentsLayer);
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

    if (!hideDistrictBoundaries && districtid) {
        loadDistrictBoundaries(districtid, function(boundaries) {
            showDistrictBoundaries(map, boundaries)
        });
    }

    return {
        map: map,
        monumentsLayer: monumentsLayer,
        finalizeMonumentsLayer: function() {
            if (autofit) {
                map.fitBounds(monumentsLayer.getBounds());
            }

            this.map.addLayer(this.monumentsLayer);
        }
    }
}

function createOuterPolygon(polygons)
{
    var world = [
        L.latLng([90, 360]),
        L.latLng([90, -180]),
        L.latLng([-90, -180]),
        L.latLng([-90, 360])
    ];
    return [world].concat(polygons);
}

function showDistrictBoundaries(map, boundaries)
{
    if (boundaries && boundaries.length > 0) {
        var outerPolygon = createOuterPolygon(boundaries);
        var polygonStyle = {
            weight: 0,
            fillColor: '#555555',
        };
        var polygon = L.polygon(outerPolygon, polygonStyle);
        polygon.addTo(map);
    }
}

function initSinglePage(pageName)
{
    var lat = getUrlParameter('lat');
    var long = getUrlParameter('long');
    var zoom = getUrlParameter('zoom');
    var districtid = getUrlParameter('districtid');
    var hideBoundaries = getBooleanUrlParameter('hide-boundaries');
    var hideMarkers = getBooleanUrlParameter('hide-markers');
    var hideDistrictBoundaries = getBooleanUrlParameter('hide-district-boundaries');
    var disableAutoFit = getBooleanUrlParameter('disable-auto-fit');
    var showAddress = getBooleanUrlParameter('show-address', undefined, null);
    var autoBoundaryMarkers = getBooleanUrlParameter('auto-boundary-markers', undefined, null);
    var highlightKnid = getUrlParameter('highlight-knid');
    if (showAddress === null) {
        showAddress = pageName.indexOf('Природные_памятники') !== 0;
    }

    document.title = pageName + ' - Wikivoyage Map';

    loadMapDataWithMonuments(pageName, function (mapData, monumentsWithMarkers) {
        lat = lat !== null ? lat : mapData['lat'];
        long = long !== null ? long : mapData['long'];
        zoom = zoom !== null ? zoom : mapData['zoom'];
        districtid = districtid !== null ? districtid : mapData['districtid'];

        if (highlightKnid) {
            $.each(monumentsWithMarkers, function (_, monument) {
                if (monument[MonumentFields.KNID] === highlightKnid) {
                    var monumentLat = monument[MonumentFields.LAT];
                    var monumentLong = monument[MonumentFields.LONG];
                    if (monumentLat && monumentLong) {
                        lat = monumentLat;
                        long = monumentLong;
                        if (mapData['zoom']) {
                            zoom = mapData['zoom'] + 3;
                        }
                    }
                }
            });
        }


        var mapObject = initializeMap(lat, long, zoom, districtid, hideDistrictBoundaries, disableAutoFit);

        if (!hideMarkers) {
            addMarkersToMap(mapObject.monumentsLayer, monumentsWithMarkers);
            mapObject.finalizeMonumentsLayer();
        }

        if (!hideBoundaries) {
            loadBoundaries(pageName, function (monumentsWithBoundaries) {
                addBoundariesToMap(mapObject.map, monumentsWithBoundaries);

                if (autoBoundaryMarkers) {
                    addAutoBoundaryMarkers(mapObject.monumentsLayer, monumentsWithMarkers, monumentsWithBoundaries);
                }
            }, showAddress);
        }
    }, showAddress);
}

function addAutoBoundaryMarkers(monumentsLayer, monumentsWithMarkers, monumentsWithBoundaries)
{
    var knidsWithMarkers = monumentsWithMarkers.map(function(monument) {
        return monument[MonumentFields.KNID];
    });

    $.each(monumentsWithBoundaries, function(_, monument) {
        if (knidsWithMarkers.indexOf(monument[MonumentFields.KNID]) === -1) {
            if (monument['boundary-coordinates'].length === 1) {
                var boundaryCoordinates = monument['boundary-coordinates'][0];
                var center = getPolygonApproximateCenter(boundaryCoordinates);
                var lat = center[0];
                var long = center[1];

                addMarkerToMap(monumentsLayer, monument, lat, long);
            }
        }
    });
}

function getPolygonApproximateCenter(polygon) {
    return polygon.reduce(function (x, y) {
        return [
            x[0] + y[0]/polygon.length,
            x[1] + y[1]/polygon.length
        ]
    }, [0,0]);
}

function initMultiplePages(pageNames)
{
    var lat = getUrlParameter('lat');
    var long = getUrlParameter('long');
    var zoom = getUrlParameter('zoom');
    var districtid = getUrlParameter('districtid');
    var sequential = getBooleanUrlParameter('sequential');
    var hideBoundaries = getBooleanUrlParameter('hide-boundaries');
    var hideMarkers = getBooleanUrlParameter('hide-markers');
    var hideDistrictBoundaries = getBooleanUrlParameter('hide-district-boundaries');
    var disableAutoFit = getBooleanUrlParameter('disable-auto-fit');
    var showAddress = getBooleanUrlParameter('show-address', undefined, null);

    var executionFunction = sequential ? runSequence : runAsync;

    var mapObject = initializeMap(lat, long, zoom, districtid, hideDistrictBoundaries, disableAutoFit);

    var loadMarkersFunctions = [];
    var loadBoundariesFunctions = [];

    pageNames.forEach(function(pageName) {
        var pageShowAddress = showAddress;
        if (pageShowAddress === null) {
            pageShowAddress = pageName.indexOf('Природные_памятники') !== 0;
        }

        if (!hideMarkers) {
            loadMarkersFunctions.push(
                function (onSuccess) {
                    loadMonuments(pageName, function (monuments) {
                        onSuccess(monuments);
                    }, pageShowAddress);
                }
            );
        }
        if (!hideBoundaries) {
            loadBoundariesFunctions.push(
                function (onSuccess) {
                    loadBoundaries(pageName, function (monuments) {
                        addBoundariesToMap(mapObject.map, monuments);
                        onSuccess();
                    }, pageShowAddress);
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
        var pageNames = pageNamesRaw.split('|');
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