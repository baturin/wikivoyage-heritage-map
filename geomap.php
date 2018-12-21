<!DOCTYPE html>
<html>
<!-- 
GeoMap:
  Version 2017-01-01
Author:
  https://de.wikivoyage.org/wiki/User:Mey2008
Contributors:
  no
License: 
  Affero GPL v3 or later http://www.gnu.org/licenses/agpl-3.0.html
Recent changes:
  2017-01-01: - E-Mail
  2016-07-31: Layer=W in Vorlagen, POI nr=||
  2016-07-17: New geocoder
  2016-07-13: Wikimedia tiles(Mapquest stop service)
  2015-11-01: Minimap fixed
  2015-09-22: new translate for layers
  2015-09-14: tidy and debug script
  2015-09-09: external content warning
  2015-08-10: url parameter page
  2015-08-09: url parameters lang, location + viewbox
  2015-05-26: mapmask
ToDo:
  nothing
-->
  <head>  
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Wikivoyage - GeoMap (Copy templates)</title>
    <link rel="icon" href="./lib/images/favicon.png" type="image/png" />
    <link rel="stylesheet" href="./lib/leaflet.css" />
    <link rel="stylesheet" href="./lib/geomap.css" />
    <link rel="stylesheet" href="./lib/Control.Geocoder.css" />
    
  </head>
  <body>
    <div id="wrap">
      <div id="left">
        <img src="https://upload.wikimedia.org/wikipedia/commons/2/2e/Wikivoyage-Logo-v3-small-en.png"  width="100px" alt="Logo" >
        <form action="" name="myform" autocomplete="off">
          <b>Copy templates</b>
          <hr>
          <input type="radio" name="group1" value="Latlong" checked="checked" onClick="choice()"> lat | long <br>
          <input type="radio" name="group1" value="Marker" onClick="choice()"> Marker <br>
          <input type="radio" name="group1" value="Geo" onClick="choice()"> Geo <font color="lime">&#10011;</font> <br>
          <input type="radio" name="group1" value="Mapframe" onClick="choice()"> Mapframe <font color="lime">&#10011;</font> <br>
          <br>
          <input type="radio" name="group1" value="Maps" onClick="choice()"> Maps <font color="lime">&#10011;</font> <br>
          <input type="radio" name="group1" value="Poi" onClick="choice()"> Poi <br>
          <input type="radio" name="group1" value="Poimap" onClick="choice()"> PoiMap2 <font color="lime">&#10011;</font> <br>
          <input type="radio" name="group1" value="Osmpoi" onClick="choice()"> OsmPoi <br>
          <input type="radio" name="group1" value="Geodata" onClick="choice()"> GeoData <font color="lime">&#10011;</font> <br>
          <br>
          <input type="radio" name="group1" value="Gpxwpt" onClick="choice()"> GpxWpt <br>
        </form>
      <br>
      <b>How it works:</b>
      <hr>
      ■ select template<br>
      ■ search destination<br>
      ■ or drag &amp; zoom<br>
      ■ click &amp; mark text<br>
      ■ copy to clipboard<br>
      ■ paste into article<br>
      ■ modify <i style='background-color:#FFCCCC'>marked</i> text<br>
    </div> <!-- left -->
  <div id="map">
    <div id="logo">
      <img src="./lib/images/logo.png" alt= "Logo" title= "Version 2016-07-31" width="64" height="64">
    </div>
    <script type="text/javascript" src="./lib/leaflet.js"></script>
    <script type="text/javascript" src="./lib/buttons-new.js"></script>
    <script type="text/javascript" src="./lib/zoomdisplay.js"></script>
    <script type="text/javascript" src="./lib/Control.Geocoder.js"></script>
    <script type="text/javascript" src="./lib/Control.MiniMap.js"></script>
    <script type="text/javascript" src="./lib/i18n.js"></script>
    <script type="text/javascript" src="./locale/<?php echo $_GET['lang'] ?: 'en'; ?>.js"></script>
    <script type="text/javascript" src="./lib/maptiles.js"></script>

    <noscript> 
      <h2><a href="http://activatejavascript.org/en/">This application needs JavaScript. - See instructions:</a></h2>
    </noscript>
    
<script>

  var lang = "<?php echo $_GET['lang'] ?: 'en'; ?>";
  L.registerLocale(lang, mylocale);
  L.setLocale(lang);
  
  maptiles();

function choice() {
  if (document.myform.group1[2].checked === true || document.myform.group1[3].checked === true || document.myform.group1[4].checked === true || document.myform.group1[6].checked === true || document.myform.group1[8].checked === true ) {
    document.getElementById("center").style.opacity = "1";
  }
  else {
    document.getElementById("center").style.opacity = "0";
  }
}

function onAll() {
  map.setView([40,15],2);
  return false;
}

function onMapClick(e) {
  var fmlat=e.latlng.lat.toFixed(map.getZoom() * 0.25 + 0.5);
  var fmlng=e.latlng.lng.toFixed(map.getZoom() * 0.25 + 0.5);

  if (document.myform.group1[0].checked === true) {
    popup.setLatLng(e.latlng).setContent('lat=' + fmlat + ' | long=' + fmlng + '<br><br>' + fmlat + '|' + fmlng + '<br><br>' + fmlat + '<br>' + fmlng).openOn(map);
  }
  else if (document.myform.group1[1].checked === true) {
    popup.setLatLng(e.latlng).setContent("{{Marker|type=<i style='background-color:#FFCCCC'>city</i> |lat=" + fmlat + " |long=" + fmlng + " |zoom=" + map.getZoom() + " |name= |image=}}").openOn(map);
  }
  else if (document.myform.group1[2].checked === true) {
    
    popup.setLatLng(e.latlng).setContent("{{geo|" + fmlat + "|" + fmlng + "|zoom=" + map.getZoom() + "}}").openOn(map);
  }
  else if (document.myform.group1[3].checked === true) {
    popup.setLatLng(e.latlng).setContent("{{Mapframe|" + fmlat + "|" + fmlng + "|zoom=" + map.getZoom() + "}}").openOn(map);
  }
  else if (document.myform.group1[4].checked === true) {
     popup.setLatLng(e.latlng).setContent("{{Maps|" + fmlat + "|" + fmlng + "|" + map.getZoom() + "<i style='background-color:#FFCCCC'>|W|Stadtplan</i>}}").openOn(map);
  }
  else if (document.myform.group1[5].checked === true) {
    popup.setLatLng(e.latlng).setContent("{{Poi||<i style='background-color:#FFCCCC'>see</i>|" +  fmlat + "|" + fmlng + "|<i style='background-color:#FFCCCC'>name</i>}}<br><br>{{Poi||<i style='background-color:#FFCCCC'>see</i>|" +  fmlat + "|" + fmlng + "|<i style='background-color:#FFCCCC'>name</i>|<i style='background-color:#FFCCCC'>image</i>|<i style='background-color:#FFCCCC'>W</i>}}").openOn(map);
  }
  else if (document.myform.group1[6].checked === true) {
    popup.setLatLng(e.latlng).setContent("[[File:<i style='background-color:#FFCCCC'>Map-icon.svg</i>|thumb|link={{PoiMap2|"  + fmlat + "|" + fmlng + "|" + map.getZoom() + "}}|<i style='background-color:#FFCCCC'>PoiMap</i>]]<br><br>{{PoiMap2|"  + fmlat + "|" + fmlng + "|" + map.getZoom() + "}}").openOn(map);
  }
  else if (document.myform.group1[7].checked === true) {
    popup.setLatLng(e.latlng).setContent("{{OsmPoi|" + fmlat + "|" + fmlng + "|" + map.getZoom() + "|<i style='background-color:#FFCCCC'>W</i>}}").openOn(map);
  }
  else if (document.myform.group1[8].checked === true) {
    popup.setLatLng(e.latlng).setContent("{{GeoData| lat= " + fmlat + "| long= "+ fmlng + "| prec= | radius= | elev= | elevMin= | elevMax= }}").openOn(map);
  }
  else if (document.myform.group1[9].checked === true) {
    popup.setLatLng(e.latlng).setContent('&lt;wpt lat="' + fmlat + '" lon="'+ fmlng + '"&gt;&lt;name&gt<i style="background-color:#FFCCCC">description</i>&lt;/name&gt;&lt;/wpt&gt;').openOn(map);
  }
  else {
    alert ("ERROR GeoMap #206, please report.");
  }
}

function get_url_param(name) {
  name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
  var regexS = "[\\?&]" + name + "=([^&#]*)";
  var regex = new RegExp(regexS);
  var results = regex.exec(window.location.href);
  if (results == null) return "";
  else return results[1];
}

function httpGet(theUrl) {
    var xmlHttp = new XMLHttpRequest();
    xmlHttp.open( "GET", theUrl, false );
    xmlHttp.send( null );
    result = xmlHttp.responseText;
    return result;
}

// read URL parameters
var ulat = get_url_param('lat');
if (ulat === "") {
  ulat = 40;
}
var ulon = get_url_param('lon');
if (ulon === "") {
  ulon = 15;
}
var uzoom = get_url_param('zoom');
if (uzoom === "") {
  uzoom = 2;
}
var ulayers = get_url_param('layers').toUpperCase();
if (ulayers === "") {
  ulayers = "W";
}
var ulang = get_url_param('lang').toLowerCase();
if (ulang === "") {
  ulang = "en";
}
var upage = get_url_param('page');
if (upage === "") {
  upage = " ";
}
var ulocation = decodeURIComponent(get_url_param('location'));
if (ulocation === "") {
  ulocation = " ";
}

var latpos, lonpos = 0;
var search, setIcon = " ";
if (ulat != 40 && ulocation != " ") {
  search = 'http://nominatim.openstreetmap.org/?street=' + ulocation + '&viewbox=' + (+ulon-0.2) + ',' + (+ulat+0.2) + ',' + (+ulon+0.2) + ',' + (+ulat-0.2) + '&bounded=1&format=xml';
  httpGet(search);
  latpos = result.indexOf("lat=");
  lonpos = result.indexOf("lon=");
  if (latpos > 0) {
    ulat = result.substring(latpos + 5, latpos + 14);
    ulon = result.substring(lonpos + 5, lonpos + 14);
    uzoom = 17;
    setIcon = "yes";
  }
  else {
    alert(ulocation + "\n\nSorry, that location could not be found!");
    ulocation = " ";    
  }
}
if (upage != " " && ulocation != " ") {
  search = 'http://nominatim.openstreetmap.org/?q=' + ulocation + ', ' + upage + '&format=xml';
  httpGet(search);
  latpos = result.indexOf("lat=");
  lonpos = result.indexOf("lon=");
  if (latpos > 0) {
    ulat = result.substring(latpos + 5, latpos + 14);
    ulon = result.substring(lonpos + 5, lonpos + 14);
    uzoom = 17;
    setIcon = "yes";
  }
  else {
    alert(ulocation + "\n\nSorry, that location could not be found!");
    ulocation = " ";    
  }
}
if (upage != " " && ulocation == " ") {
  search = 'http://nominatim.openstreetmap.org/?q=' + upage + '&format=xml';
  httpGet(search);
  latpos = result.indexOf("lat=");
  lonpos = result.indexOf("lon=");
  if (latpos > 0)      {
    ulat = result.substring(latpos + 5, latpos + 14);
    ulon = result.substring(lonpos + 5, lonpos + 14);
    uzoom = 15;
    setIcon = "no";
  }
}
if (latpos < 0) {
  alert("Sorry, that location could not be found!");
  setIcon = "no";
  uzoom = 2;
}

var map = L.map('map',{zoomControl: false, minZoom:2, maxZoom: 18}).setView([ulat, ulon], uzoom);

// Base layer "Wikimedia (default layer)" https
if (ulayers.indexOf('W') != -1) {
  map.addLayer(wikimedia);
}    

// Basislayer "mapnik " http & https
if (ulayers.indexOf('M') != -1) {
  map.addLayer(mapnik);
}    

// Layer "Boundaries (default layer)" http
if (ulayers.indexOf('B') != -1) {
  map.addLayer(boundaries);
} 

// Layer "Cycling" http
if (ulayers.indexOf('C') != -1) {
  map.addLayer(cycling);
}

// Mini map, layer "Wikimedia" https

  tilesUrl = 'https://maps.wikimedia.org/osm-intl/{z}/{x}/{y}.png';
  tilesAttrib = L._("Map data") + ' © <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a> ' + L._("contributors, Tiles") + ' © <a href="https://wikimediafoundation.org/wiki/Home">Wikimedia</a>';
  var wmmini = new L.TileLayer(tilesUrl, {attribution: tilesAttrib});
  var miniMap = new L.Control.MiniMap(wmmini, { toggleDisplay: true,	width: 250 }).addTo(map);

// target
if (setIcon == "yes") {
  var redIcon = L.icon({iconUrl: './ico24/target.png', iconSize: [32,32], iconAnchor: [16,16]});
  L.marker([ulat, ulon],{icon: redIcon}).addTo(map);
}
// MapMask 
var mask =  [[90, -180],[90, 180],[-90, 180],[-90, -180]];
var mcolor = "black", mweight = 0, mopacity = 0, mfillOpacity = 0.2;
if (L.Browser.android) {
var mcolor = "blue", mweight = 5, mopacity = 0.2, mfillOpacity = 0;
}
var mapmask = L.polygon(
  [[[90, -540],[90,540],[-90, 540],[-90, -540]],mask], // world, mask
  {color: mcolor, weight: mweight, opacity: mopacity, fillOpacity: mfillOpacity, clickable: false}
).addTo(map); 

// Controls

var basemaps = {
  'Wikimedia': wikimedia,
  'Mapnik': mapnik
}; 
var overlays = {
  'Boundaries': boundaries,
  'Cycling': cycling
};

basemaps[L._("Wikimedia") + ' <img src="./lib/images/wmf-logo-12.png" />'] = basemaps.Wikimedia;
basemaps[L._('Mapnik') + ' <img src="./lib/images/external.png" />'] = basemaps.Mapnik;

overlays[L._("Boundaries") + ' <img src="./lib/images/external.png" />'] = overlays.Boundaries;
overlays[L._("Cycling") + ' <img src="./lib/images/external.png" />'] = overlays.Cycling;
    
var maptype = "geomap";

map.addControl(new L.Control.Geocoder({placeholder: L._("Locate!")}));
map.addControl(new L.Control.Layers(basemaps, overlays));
map.addControl(new L.Control.Scale());
map.addControl(new L.Control.Buttons());

// External content warning
var imgpath = "../lib/images/"; 
if (L.Browser.ie) {
  imgpath = "./lib/images/";
}
var warning = 'url(' + imgpath + 'line.png) "' + L._('Content with {external} is hosted externally, so enabling it shares your data with other sites.',{external:' "url(' + imgpath + 'external.png)" '}) + '"';
document.styleSheets[1].cssRules[4].style.content = warning;

// Pop-up coordinates
var popup = L.popup({maxWidth: 800});

map.on('click', onMapClick);

</script>

        <div id="center">
            <img src="./lib/images/center.png"> 
        </div>
      </div> <!-- map -->
    </div> <!-- wrap -->
  </body>
</html>
