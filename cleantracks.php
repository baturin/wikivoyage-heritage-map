<!DOCTYPE html>
<html>
  <!-- 
  cleantracks.php - version 2015-09-10

  Author:
    https://de.wikivoyage.org/wiki/User:Mey2008
  License: 
    Affero GPL v3 or later http://www.gnu.org/licenses/agpl-3.0.html
  Recent changes:
    no
  ToDo:
    nothing
  -->
  <head>
    <title>Wikivoyage - Clean tracks.gpx</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
  </head>
  <body>
  <h1>Clean tracks.gpx</h1>
  <?php
  $gpxcontent = file_get_contents("./lib/empty.gpx");

  $fp = fopen("../x/tracks.gpx", "wb+");
   fwrite($fp, $gpxcontent);
  fclose($fp);
  
  $fp = fopen("../w/tracks.gpx", "wb+");
   fwrite($fp, $gpxcontent);
  fclose($fp);
  ?>
  <h3>Done.</h3>
  </body>
</html>

