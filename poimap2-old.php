<!DOCTYPE html>
<html>
<!-- 
PoiMap2:
  Version 2015-09-22
Author:
  https://de.wikivoyage.org/wiki/User:Mey2008    
Contributors:
  https://en.wikivoyage.org/wiki/User:Torty3
  https://en.wikivoyage.org/wiki/Benutzer:Nicolas1981
  https://it.wikivoyage.org/wiki/Utente:Andyrom75
License: 
  Affero GPL v3 or later https://www.gnu.org/licenses/agpl-3.0.html 
Recent changes:
  2015-09-22: new translate for layers
  2015-09-15: debug script
  2015-09-09: track.gpx only when gpxcontent
  2015-09-06: fix GPX error
  2015-08-30: external content warning
  2015-07-15: http: to https:
  2015-07-06: links to listings
  2015-05-22: maptiles.js
  2015-05-20: layer articles now wvarticles
  2015-05-20: layer bw now mapnikbw  
ToDo:
  nothing
-->
   
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title> <?php echo $_GET["name"]," — Wikivoyage Map" ?></title>
    <link rel="icon" href="./lib/images/favicon.png" type= "image/png" />
    <link rel="stylesheet" href="./lib/leaflet.css" />
    <link rel="stylesheet" href="./lib/poimap.css" />
    <link rel="stylesheet" href="./lib/PruneCluster.css" />
    <link rel="stylesheet" href="./lib/locate.css" />
    <link rel="stylesheet" href="./lib/Control.OSMGeocoder.css" />
  </head>
<body>
<div id="map">
  <div id="logo">
    <img src="./lib/images/logo.png" alt= "Logo" title= "Version 2015-09-22" width="64" height="64">
  </div>
  <script type="text/javascript" src="./lib/leaflet.js"></script>
  <script type="text/javascript" src="./lib/Leaflet.EdgeMarker.js"></script>
  <script type="text/javascript" src="./lib/leaflet.markercluster.js"></script>
  <script type="text/javascript" src="./lib/PruneCluster.js"></script>
  <script type="text/javascript" src="./lib/buttons-new.js"></script>
  <script type="text/javascript" src="./lib/zoomdisplay.js"></script>
  <script type="text/javascript" src="./lib/markers.js"></script>
  <script type="text/javascript" src="./lib/gpx.js"></script>
  <script type="text/javascript" src="./lib/locate.js"></script>
  <script type="text/javascript" src="./lib/Control.OSMGeocoder.js"></script>
  <script type="text/javascript" src="./lib/i18n.js"></script>
  <script type="text/javascript" src="./data/<?php echo $_GET['lang'] ?: 'en'; ?>-articles.js"></script>
  <script type="text/javascript" src="./locale/<?php echo $_GET['lang'] ?: 'en'; ?>.js"></script>
  <script type="text/javascript" src="./lib/maptiles.js"></script>
  
<?php

/* //PHP error reporting  *** TEST ***
error_reporting (E_ALL | E_STRICT);
ini_set ('display_errors' , 1);
*/

include ('./readpage.php');

$gpxcontent = "";
if ($lang == 'el' || $lang == 'en' || $lang == 'fr' || $lang == 'it' || $lang == 'nl' || $lang == 'ru') {
  // Gpx data --> Template:GPX/Articlename
  $gpxcontent = @file_get_contents("https://" . $lang . ".wikivoyage.org/w/index.php?title=Template:GPX/" . $file . "&action=raw");
}
else {
  // Gpx data --> Articlename/Gpx
  $gpxcontent = @file_get_contents("https://" . $lang . ".wikivoyage.org/w/index.php?title=" . $file . "/Gpx&action=raw");
}
if (!$gpxcontent) {
  $gpxdata = "no";
}
else {
// gpx.js needs seq. file
  $gpxdata = "yes";
  $fp = fopen("./tracks.gpx", "wb+");
    fwrite($fp, $gpxcontent);
  fclose($fp);
}

// search for fixed color
$fixedcolor = strpos($gpxcontent, 'fixedcolor="yes"');

// echo '<pre>'; print_r($GLOBALS); echo '</pre>'; // *** TEST ***

?>

<noscript> 
 <h2><a href="http://activatejavascript.org/en/">This application needs JavaScript. - See instructions:</a></h2>
</noscript>

<script type='text/javascript'>

// stop for testing // *** TEST ***
// alert("stop for testing"); // *** TEST ***

  var lang = "<?php echo $_GET['lang'] ?: 'en'; ?>";
  L.registerLocale(lang, mylocale);
  L.setLocale(lang);
  
  maptiles();

  var zoomtoggle= "center";
  var layertoggle= "pois";

  function sleep(ms) {
    ms += new Date().getTime();
    while (new Date() < ms){}
  }

  function onAll() {
    map.removeLayer(wvarticles);
    map.addLayer(markers);
    map.addLayer(tracks);    
    if (zoomtoggle == "center") {
      map.fitBounds(markers.getBounds());
      zoomtoggle= "all";
      layertoggle= "pois";
    }
    else {
      map.setView([jslat,jslon],jszoom,true);
      zoomtoggle= "center";
      layertoggle= "pois";
    } 
  }
    
  function onDest() {
    if (layertoggle == "pois") {
      map.removeLayer(markers);
      map.removeLayer(tracks);
      map.addLayer(wvarticles);
      map.setView([jslat,jslon],destzoom,true);
      layertoggle= "destinations";
      zoomtoggle= "center";
    }
    else {
      map.removeLayer(wvarticles);
      map.addLayer(markers);
      map.addLayer(tracks);
      map.setView([jslat,jslon],jszoom,true);
      layertoggle= "pois";
      zoomtoggle= "center";
    }
  }

  function onMapMenu(e) {
  var fmlat=e.latlng.lat.toFixed(map.getZoom() * 0.25 + 0.5);
  var fmlng=e.latlng.lng.toFixed(map.getZoom() * 0.25 + 0.5);
    popup
    .setLatLng(e.latlng)
    .setContent(L._('You clicked the map at') + '<br> lat=' + fmlat + ' | long=' + fmlng)
    .openOn(map);
  }

  // All arrays to js
  var jslat   =  '<?php echo $_GET["lat"] ?: "0";?>';
  if (isNaN(jslat)) { jslat= "0"; alert("ERROR: Lat must be numeric!");}
  jslat =parseFloat(jslat);
  var jslon   =  '<?php echo $_GET["lon"] ?: "0"; ?>';
  if (isNaN(jslon)) { jslon= "0";alert("ERROR: Lon must be numeric!");}
  jslon =parseFloat(jslon);
  var jszoom  =  '<?php echo $_GET["zoom"] ?: "14"; ?>';
  var autozoom = "no";
  if (jszoom == "auto") {autozoom = "yes";}
	if (parseInt(jszoom) < 0 | parseInt(jszoom) > 18 | isNaN(jszoom) | jslat === 0 | jslon === 0) {jszoom = 10;}
  var jslayer = '<?php echo $_GET["layer"] ?: "M"; ?>'.toUpperCase();
  if (jslayer == "UNDEFINED") {jslayer = "M";}
  if (jslayer == "E") {jslayer = "ODE-P";}
  var jslang  = '<?php echo $_GET["lang"]; ?>'.toLowerCase();

  var jsmax = <?php echo $max; ?>;
  var jsp =   <?php echo json_encode($p); ?>;
  var jsc =   <?php echo json_encode($c); ?>;
  var jsx =   <?php echo json_encode($x); ?>;
  var jsy =   <?php echo json_encode($y); ?>;
  var jsn =   <?php echo json_encode($n); ?>;
  var jsf =   <?php echo json_encode($f); ?>;

  var jfixcol = <?php echo $fixedcolor ?: "0"; ?>;
  var jgpxdata = '<?php echo $gpxdata; ?>';
  var artname = "<?php echo $_GET["name"]?>";
  var mask =  <?php echo $mask; ?>;

  // Make map 
  var map = new L.Map('map', {center: new L.LatLng(jslat,jslon), zoom: jszoom, zoomControl: false, doubleClickZoom: false});
  if (jslang == "en") {
    map.scrollWheelZoom.disable();
  }
  var popup = L.popup();

  map.on('click', function(e) {
    map.scrollWheelZoom.enable();
  });

  map.on('contextmenu', onMapMenu);

  map.on('dblclick', function(e) {
    map.setView(e.latlng, map.getZoom() + 1);
  });

  // Base layer "Mapquestopen" https
  if (jslayer.indexOf("O") != -1) {
    map.addLayer(mapquestopen);
  }

  // Base layer "Mapquest" https
  if (jslayer.indexOf("A") != -1) {
    map.addLayer(mapquest);
  }

  // Base layer "Mapnik (wmf)" https
  if (jslayer.indexOf("M") != -1 || jslayer.indexOf("C") != -1){
    map.addLayer(mapnik);
  }
  
  // Base layer "Mapnik b&w" http
  if (jslayer.indexOf("W") != -1) {
    map.addLayer(mapnikbw);
  }

  // Base layer "Transport" http
  if (jslayer.indexOf("N") != -1) {
    map.addLayer(transport);
  }

  // Base layer "Landscape" http
  if (jslayer.indexOf("R") != -1) {
    map.addLayer(landscape);
  }

  // Layer "Labels" https
  if (jslayer.indexOf("L") != -1) {
    map.addLayer(maplabels);
  }

  // Layer "Boundaries" http
  if (jslayer.indexOf("B") != -1) {
    map.addLayer(boundaries);
  }
  
  // Layer "Cycling" http
  if (jslayer.indexOf("C") != -1) {
    map.addLayer(cycling);
  }

  // Layer "Hiking trails" http
  if (jslayer.indexOf("H") != -1) {
    map.addLayer(hiking);
  }

  // Layer "Hill shading" http
  if (jslayer.indexOf("S") != -1) {
    map.addLayer(hill);
  }
  
  // load local image
  function imgError(image) {   
    image.onerror = "";
    image.src = image.src.replace("wikipedia/commons","wikivoyage/" + jslang);
    return true;
  } 

  // Layer "POI"
  // var markers = new L.featureGroup();
  var markers = new L.MarkerClusterGroup({
    showCoverageOnHover: false, maxClusterRadius: 13, iconCreateFunction: function(cluster) {
      return L.icon({iconUrl: './ico24/cluster.png', iconAnchor: [12,12]});
    }
  });
  var mi = 1;
  var tooltip = "no";
  var poilink = "no";
  while(mi <= jsmax){
  if (jsx[mi] != "0"){
    var tooltip = jsn[mi].replace("<br />","").replace("[[","");
    var article = tooltip;
    if (jsn[mi].indexOf("[[") === 0) {
      article=  '<a href= "https://' + jslang + '.wikivoyage.org/wiki/' + tooltip.replace(/ /g, "_") + '"title="Link to article" target="_blank">' + tooltip + '</a>';
      tooltip= tooltip + " \u2197";
    }
    else if (jslang == "it") {
      poilink = encodeURI(tooltip).replace(/%20/g, "_").replace(/%/g, ".");
      article=  '<a href= "https://' + jslang + '.wikivoyage.org/wiki/' + artname.replace(/ /g, "_") + '#' + poilink + '"title="Link to listing" target="_blank">' + tooltip + '</a>';
      tooltip= tooltip + " \u2197";
    }
    if (jsf[mi] == "0/01/no"){
      var content = article;
      var minw = 10;
      var maxw = 240;
    }
    else {
      tooltip = tooltip + " \u273f";
      var imgurl = '"https://' + jslang + '.m.wikivoyage.org/wiki/File:' + jsf[mi].substr(5) + '" target="_blank"';
      var content = '<a href = ' + imgurl + '><img src="https://upload.wikimedia.org/wikipedia/commons/thumb/' + 
      jsf[mi] + '/120px-' + jsf[mi].substr(5) + '" width="120" title="⇱⇲" onerror="imgError(this);"></a><br />' + article + '&nbsp;<a href = ' + imgurl + '>';
      var minw = 120;
      var maxw = 120;
    }
    var zio = 1000 - (mi * 2);
    var marker = new L.Marker([jsx[mi], jsy[mi]], {title: tooltip, zIndexOffset: zio, 
      icon: new L.NumberedDivIcon({number: jsp[mi], 
      iconUrl: "./ico24/" + jsc[mi] + ".png"
     })}).bindPopup(content,{minWidth:minw, maxWidth:maxw}).addTo(markers);
    }
    mi++;
  }

  if (jslayer.indexOf("-P") == -1) {
   map.addLayer(markers);
   L.edgeMarker({"radius":10,"weight":3}).addTo(map);
  }

  if (autozoom == "yes" && tooltip != "no") {
    map.setView([0,0],jszoom);
    map.fitBounds(markers.getBounds());
    jslat = map.getCenter(markers).lat;
    jslon = map.getCenter(markers).lng;
    jszoom = map.getZoom(markers);
  }

  function mousepopup (marker, data) {
    marker.bindPopup(data.title,{minWidth:120, maxWidth:120});
    marker.on('mouseover', function (e) {
      this.openPopup();
    });
  }
  
  // Layer wvarticles
  var destzoom= 9;
  var nr = (addressPoints.length);
  if (navigator.appVersion.substring(0, 1) == 4){
    nr = nr - 1; // fix for old Explorers
  }
  var wvarticles = new PruneClusterForLeaflet(70);
  var a = addressPoints[0];
  var tp = '//upload.wikimedia.org/wikipedia/commons/thumb/'; // thumbnail path
  var ap = '//' + jslang + '.wikivoyage.org/wiki/'; // WV article path
  for (var i = 0; i < nr; i++) {
    a = addressPoints[i];
    wvarticles.RegisterMarker(new PruneCluster.Marker(a[0], a[1], {title:'<img src="' + tp + a[3] + '/120px-' + a[3].substring(5) + '"> <a href="' + ap + a[2] + '" target="_blank">' + a[2] + '</a>'}));
    wvarticles.PrepareLeafletMarker = mousepopup;
  }
  if (jslayer.indexOf("D") != -1) {
    map.addLayer(wvarticles);
  }

  // GPX-Layer
  if (jgpxdata == "yes"){
    var tracks = new L.GPX("./tracks.gpx", {async: true});
  //    .on("loaded", function(e) {} );
    map.addLayer(tracks);
  }
  else {
    var tracks = new L.GPX("./lib/empty.gpx", {async: true});
  //    .on("loaded", function(e) {} );
  } 
  
  // MapMask 
  var mcolor = "black", mweight = 0, mopacity = 0, mfillOpacity = 0.2;
  if (L.Browser.android) {
  var mcolor = "blue", mweight = 5, mopacity = 0.2, mfillOpacity = 0;
  }
  if (mask != "") {
    var mapmask = L.polygon(
      [[[90, -180],[90, 180],[-90, 180],[-90, -180]],mask], // world, mask
      {color: mcolor, weight: mweight, opacity: mopacity, fillOpacity: mfillOpacity, clickable: false}
    ).addTo(tracks); 
    map.addLayer(tracks);
  }

  // Controls
  
  if (jslayer.indexOf("E") != -1) {
    map.addControl(new L.Control.OSMGeocoder({collapsed: false, text: L._("Locate!")}));
  }

  var basemaps = {
      'Mapnik': mapnik,
      'Mapnik_bw': mapnikbw,
      'Mapquest_open': mapquestopen,
      'Mapquest_aerial': mapquest,
      'Traffic_line_network': transport,
      'Relief_map': landscape
  };
  var overlays = {
      'Mapquest_labels': maplabels,
      'Boundaries': boundaries,
      'Hill_shading': hill,
      'Cycling': cycling,
      'Hiking': hiking,
      'Points_of_interest': markers,
      'Destinations': wvarticles,
      'GPX_tracks_Map_mask': tracks
    };
    
  basemaps[L._('Mapnik') + ' <img src="./lib/images/wmf-logo-12.png" />'] = basemaps.Mapnik;
  basemaps[L._('Mapnik_bw') + ' <img src="./lib/images/wmf-logo-12.png" />'] = basemaps.Mapnik_bw;
  basemaps[L._('Mapquest_open') + ' <img src="./lib/images/external.png" />'] = basemaps.Mapquest_open;
  basemaps[L._('Mapquest_aerial') + ' <img src="./lib/images/external.png" />'] = basemaps.Mapquest_aerial;
  basemaps[L._('Traffic_line_network') + ' <img src="./lib/images/external.png" />'] = basemaps.Traffic_line_network;
  basemaps[L._('Relief_map') + ' <img src="./lib/images/external.png" />'] = basemaps.Relief_map;
  
  overlays[L._('Mapquest_labels') + ' <img src="./lib/images/external.png" />'] = overlays.Mapquest_labels;
  overlays[L._('Boundaries') + ' <img src="./lib/images/external.png" />'] = overlays.Boundaries;
  overlays[L._('Hill_shading') + ' <img src="./lib/images/external.png" />'] = overlays.Hill_shading;
  overlays[L._('Cycling') + ' <img src="./lib/images/external.png" />'] = overlays.Cycling;
  overlays[L._('Hiking') + ' <img src="./lib/images/external.png" />'] = overlays.Hiking;
  overlays[L._('Points_of_interest') + ' <img src="./lib/images/wv-logo-12.png" />'] = overlays.Points_of_interest;
  overlays[L._('Destinations') + ' <img src="./lib/images/wv-logo-12.png" />'] = overlays.Destinations;
  overlays[L._('GPX_tracks_Map_mask') + ' <img src="./lib/images/wv-logo-12.png" />'] = overlays.GPX_tracks_Map_mask;
 
  var maptype = "poimap2";
  
  map.addControl(new L.Control.Layers(basemaps, overlays));
  map.addControl(new L.Control.Scale());
  map.addControl(new L.Control.Buttons());
  map.addControl(new L.Control.Locate());
    
  // External content warning
  var imgpath = "../lib/images/"; 
  if (L.Browser.ie) {
    imgpath = "./lib/images/";
  }
  var warning = 'url(' + imgpath + 'line.png) "' + L._('Content with {external} is hosted externally, so enabling it shares your data with other sites.',{external:' "url(' + imgpath + 'external.png)" '}) + '"';
  document.styleSheets[1].cssRules[4].style.content = warning;
  
</script>

</div>
</body>
</html>
