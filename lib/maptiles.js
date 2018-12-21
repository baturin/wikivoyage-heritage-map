
function maptiles() {
  // "Wikimedia"
  tilesUrl = 'https://maps.wikimedia.org/osm-intl/{z}/{x}/{y}.png';
  tilesAttrib = L._("Map data") + ' © <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a> ' + L._("contributors, Tiles") + ' © <a href="https://wikimediafoundation.org/wiki/Home">Wikimedia</a>';
  wikimedia = new L.TileLayer(tilesUrl, {attribution: tilesAttrib});
  
  // "Mapnik" 
  tilesUrl = '//{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
  subDomains = ['a','b','c'];
  tilesAttribution = L._("Map data") + ' © <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a> ' + L._("contributors");
  mapnik = new L.TileLayer(tilesUrl, {attribution: tilesAttribution, subdomains: subDomains});
 
  // "Landscape" (relief)
  tilesUrl = 'http://{s}.tile.thunderforest.com/landscape/{z}/{x}/{y}.png';
  tilesAttrib = L._('Map Data') + ' © <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a> ' + L._('contributors, Tiles') + ' © <a href="http://www.opencyclemap.org/">Andy Allan</a>';
  landscape = new L.TileLayer(tilesUrl, {attribution: tilesAttrib});
}
