/* 
Version 2015-09-15

2015-09-15: WMFLabs wieder OK
2015-09-15: Mapnik tiles auf WMFLabs Server ausgefallen
2015-07-13: WMFLabs tiles server OK
2015-07-11: Ersatz tiles durch Ausfall WMFlabs Server
2015-06-26: Base layer Mapnik to OSM server for maps.wikivoyage-ev.org

*/

function maptiles() {

  // Base layer "Mapquestopen" https
  mapquestopenUrl = 'https://{s}.mqcdn.com/tiles/1.0.0/map/{z}/{x}/{y}.png', mosubDomains = ['otile1-s','otile2-s','otile3-s','otile4-s'];
  mapquestopenAttrib = L._("Map data") + ' © <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a> ' + L._("contributors, Tiles") + ' © <a href="http://open.mapquest.co.uk">MapQuest</a>';
  mapquestopen = new L.TileLayer(mapquestopenUrl, {attribution: mapquestopenAttrib, subdomains: mosubDomains});

  // Base layer "Mapquest" https
  mapquestUrl = 'https://{s}.mqcdn.com/tiles/1.0.0/sat/{z}/{x}/{y}.jpg', subDomains = ['otile1-s','otile2-s','otile3-s','otile4-s'];
  mapquestAttrib = L._('Data, imagery and map information provided by') + ' <a href="http://open.mapquest.co.uk">MapQuest</a>';
  mapquest = new L.TileLayer(mapquestUrl, {attribution: mapquestAttrib, subdomains: subDomains});

  // Base layer "mapnik" WMFlabs https 
  mapnikUrl = 'https://tiles.wmflabs.org/osm/{z}/{x}/{y}.png';
  mapnikAttribution = L._("Map data") + ' © <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a> ' + L._("contributors");
  mapnik = new L.TileLayer(mapnikUrl, {minZoom: 0, maxZoom: 18, attribution: mapnikAttribution});

  /*
  // Base layer "mapnik" OSM https - Reserve bei Ausfall WMFlabs Server
  mapnikUrl = '//{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
  mapnikAttribution = L._("Map data") + ' © <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a> ' + L._("contributors");
  mapnik = new L.TileLayer(mapnikUrl, {minZoom: 0, maxZoom: 18, attribution: mapnikAttribution});
  */
  
  // Layer "Labels" https
  maplabelsUrl = 'https://{s}.mqcdn.com/tiles/1.0.0/hyb/{z}/{x}/{y}.png', subDomains = ['otile1-s','otile2-s','otile3-s','otile4-s'];
  maplabelsAttrib = '';
  maplabels = new L.TileLayer(maplabelsUrl, {attribution: maplabelsAttrib, subdomains: subDomains});

  // Layer "Boundaries" http
  boundariesUrl = 'http://openmapsurfer.uni-hd.de/tiles/adminb/tms_b.ashx?x={x}&y={y}&z={z}';
  boundariesAttrib = '';
  boundaries = new L.TileLayer(boundariesUrl, {attribution: boundariesAttrib});

  // Layer "Cycling" http
  cyclingUrl = 'http://tile.lonvia.de/cycling/{z}/{x}/{y}.png';
  cyclingAttrib = L._('Cycling routes') + ' © <a href="http://cycling.lonvia.de">Cycling Map</a>';
  cycling = new L.TileLayer(cyclingUrl, {attribution: cyclingAttrib});

  // Base layer "Landscape" http
  landscapeUrl = 'http://{s}.tile.thunderforest.com/landscape/{z}/{x}/{y}.png';
  landscapeAttribution = L._('Map Data') + ' © <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a> ' + L._('contributors, Tiles') + ' © <a href="http://www.opencyclemap.org/">Andy Allan</a>';
  landscape = new L.TileLayer(landscapeUrl, {minZoom: 0, maxZoom: 18, attribution: landscapeAttribution});

  // Base layer "Mapnik b&w" http WMFlabs
  mapnikbwUrl = 'http://{s}.tiles.wmflabs.org/bw-mapnik/{z}/{x}/{y}.png';
  mapnikbwAttribution = L._("Map data") + ' © <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a> ' + L._("contributors");
  mapnikbw = new L.TileLayer(mapnikbwUrl, {minZoom: 0, maxZoom: 18, attribution: mapnikbwAttribution});

  /*
  // Base layer "Mapnik b&w" http - Reserve bei Ausfall WMFlabs Server
  mapnikbwUrl = 'http://{s}.www.toolserver.org/tiles/bw-mapnik/{z}/{x}/{y}.png';
  mapnikbwAttribution = L._("Map data") + ' © <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a> ' + L._("contributors");
  mapnikbw = new L.TileLayer(mapnikbwUrl, {minZoom: 0, maxZoom: 18, attribution: mapnikbwAttribution});
  */
  
  // Base layer "Transport" http
  transportUrl = 'http://{s}.tile2.opencyclemap.org/transport/{z}/{x}/{y}.png';
  transportAttribution = L._('Map Data') + ' © <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a> ' + L._('contributors, Tiles') + ' © <a href="http://www.opencyclemap.org/">Andy Allan</a>';
  transport = new L.TileLayer(transportUrl, {minZoom: 0, maxZoom: 18, attribution: transportAttribution});

  // Layer "Hiking trails" http
  hikingUrl = 'http://tile.waymarkedtrails.org/hiking/{z}/{x}/{y}.png';
  hikingAttribution = L._('Hiking trails') + ' © <a href="http://hiking.waymarkedtrails.org/de/">Hiking Map</a>';
  hiking = new L.TileLayer(hikingUrl, {minZoom: 0, maxZoom: 18, attribution: hikingAttribution});

  // Layer "Hill shading" http WMFlabs
  hillUrl = 'http://{s}.tiles.wmflabs.org/hillshading/{z}/{x}/{y}.png';
  hillAttribution = L._('Hill shading') + ' © <a href="http://www2.jpl.nasa.gov/srtm/">NASA</a>';
  hill = new L.TileLayer(hillUrl, {minZoom: 0, maxZoom: 18, attribution: hillAttribution});

  /*
  // Layer "Hill shading" http openpistemap  - Reserve bei Ausfall WMFlabs Server
  hillUrl = 'http://tiles.openpistemap.org/landshaded/{z}/{x}/{y}.png';
  hillAttribution = L._('Hill shading') + ' © <a href="http://www2.jpl.nasa.gov/srtm/">NASA</a>';
  hill = new L.TileLayer(hillUrl, {minZoom: 0, maxZoom: 18, attribution: hillAttribution});
  */
}
