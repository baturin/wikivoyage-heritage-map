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
  <script type='text/javascript' src="./lib/monument-map.js"></script>
</div>
</body>
</html>
