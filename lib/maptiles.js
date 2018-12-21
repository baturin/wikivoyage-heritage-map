/* 
Version 2015-12-20

2015-12-20: Mapnik tiles vom OSM Server
2015-11-01: Mapnik tiles vom WMFLalbs server, Layer Grenzen: neue Adresse
2015-10-05: Mapnik tiles vom OSM Server
2015-09-23: + Wikimedia layer
2015-09-15: WMFLabs wieder OK
2015-09-15: Mapnik tiles auf WMFLabs Server ausgefallen
2015-07-13: WMFLabs tiles server OK
2015-07-11: Ersatz tiles durch Ausfall WMFlabs Server
2015-06-26: Base layer Mapnik to OSM server for maps.wikivoyage-ev.org

*/

function maptiles() {
  
  // BASEMAPS
  
  // "Wikimedia"
  tilesUrl = 'https://maps.wikimedia.org/osm-intl/{z}/{x}/{y}.png';
  tilesAttrib = L._("Map data") + ' © <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a> ' + L._("contributors, Tiles") + ' © <a href="https://wikimediafoundation.org/wiki/Home">Wikimedia</a>';
  wikimedia = new L.TileLayer(tilesUrl, {attribution: tilesAttrib});
  
  /* 
  // "Mapnik"
  tilesUrl = 'https://tiles.wmflabs.org/osm/{z}/{x}/{y}.png';
  tilesAttrib = '© <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a> ' + L._("contributors");
  mapnik = new L.TileLayer(tilesUrl, {attribution: tilesAttrib});  
  */
  
  // "Mapnik" 
  tilesUrl = '//{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
  subDomains = ['a','b','c'];
  tilesAttribution = L._("Map data") + ' © <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a> ' + L._("contributors");
  mapnik = new L.TileLayer(tilesUrl, {attribution: tilesAttribution, subdomains: subDomains});
   
  // "Mapquestopen"
  tilesUrl = 'https://{s}.mqcdn.com/tiles/1.0.0/map/{z}/{x}/{y}.png';
  subDomains = ['otile1-s','otile2-s','otile3-s','otile4-s'];
  tilesAttrib = L._("Map data") + ' © <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a> ' + L._("contributors, Tiles") + ' © <a href="http://open.mapquest.co.uk">MapQuest</a>';
  mapquestopen = new L.TileLayer(tilesUrl, {attribution: tilesAttrib, subdomains: subDomains});

  // "Mapquest" (aerial)
  tilesUrl = 'https://{s}.mqcdn.com/tiles/1.0.0/sat/{z}/{x}/{y}.jpg'; 
  subDomains = ['otile1-s','otile2-s','otile3-s','otile4-s'];
  tilesAttrib = L._('Data, imagery and map information provided by') + ' <a href="http://open.mapquest.co.uk">MapQuest</a>';
  mapquest = new L.TileLayer(tilesUrl, {attribution: tilesAttrib, subdomains: subDomains});
 
  // "Landscape" (relief)
  tilesUrl = 'http://{s}.tile.thunderforest.com/landscape/{z}/{x}/{y}.png';
  tilesAttrib = L._('Map Data') + ' © <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a> ' + L._('contributors, Tiles') + ' © <a href="http://www.opencyclemap.org/">Andy Allan</a>';
  landscape = new L.TileLayer(tilesUrl, {attribution: tilesAttrib});
  
  // OVERLAYS
  
  // "Traffic"
  tilesUrl = 'http://www.openptmap.org/tiles/{z}/{x}/{y}.png';
  tilesAttrib = 'traffic lines © <a href="http://openptmap.org/">Openptmap org</a>';
  traffic = new L.TileLayer(tilesUrl, {attribution: tilesAttrib, opacity: 0.5, maxNativeZoom: 17});
  
  // "Labels" (for aerial)
  tilesUrl = 'https://{s}.mqcdn.com/tiles/1.0.0/hyb/{z}/{x}/{y}.png';
  subDomains = ['otile1-s','otile2-s','otile3-s','otile4-s'];
  tilesAttrib = '';
  maplabels = new L.TileLayer(tilesUrl, {attribution: tilesAttrib, subdomains: subDomains});

  // "Boundaries"
  tilesUrl = 'http://korona.geog.uni-heidelberg.de/tiles/adminb/x={x}&y={y}&z={z}';
  tilesAttrib = '';
  boundaries = new L.TileLayer(tilesUrl, {attribution: tilesAttrib});

  // "Cycling"
  tilesUrl = 'http://tile.lonvia.de/cycling/{z}/{x}/{y}.png';
  tilesAttrib = L._('Cycling routes') + ' © <a href="http://cycling.lonvia.de">Cycling Map</a>';
  cycling = new L.TileLayer(tilesUrl, {attribution: tilesAttrib});
  
  // "Hiking"
  tilesUrl = 'http://tile.waymarkedtrails.org/hiking/{z}/{x}/{y}.png';
  tilesAttrib = L._('Hiking trails') + ' © <a href="http://hiking.waymarkedtrails.org/de/">Hiking Map</a>';
  hiking = new L.TileLayer(tilesUrl, {attribution: tilesAttrib});

  // "Hill shading"
  tilesUrl = 'http://{s}.tiles.wmflabs.org/hillshading/{z}/{x}/{y}.png';
  tilesAttrib = L._('Hill shading') + ' © <a href="http://www2.jpl.nasa.gov/srtm/">NASA</a>';
  hill = new L.TileLayer(tilesUrl, {attribution: tilesAttrib, maxNativeZoom: 16});
}
