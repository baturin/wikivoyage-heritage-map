<!DOCTYPE html>
<html>
  <!-- 
  servertest.php - version 2014-04-18

  Author:
    https://de.wikivoyage.org/wiki/User:Mey2008
  License: 
    Affero GPL v3 or later http://www.gnu.org/licenses/agpl-3.0.html
  Recent changes:
    --
  ToDo:
    --
  -->
  <head>
    <title>Wikivoyage - Servertest</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
  </head>
  <body>
    <?php
    //PHP error reporting 
    error_reporting (E_ALL | E_STRICT);
    ini_set ('display_errors' , 1);

      echo "<b>SERVER TEST</b> <br />\n<br />\n";
      echo "&bull; Server <b>" . $_SERVER['SERVER_NAME'] . "</b> is online.<br />\n";
      echo "&bull; <b>PHP</b> works.<br />\n";
      date_default_timezone_set("UTC");
      $filename = 'poimap2.php';
      if (file_exists($filename)) {
        echo "&bull; File <b>$filename</b> was last modified (date / time): " . date("Y-m-d / H:i:s", filectime($filename)) . " UTC.";
      }
    ?>
  </body>
</html>

