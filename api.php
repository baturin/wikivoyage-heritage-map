<?php

namespace WikivoyageApi;

require_once('lib/php/TemplateReader.php');
require_once('lib/php/RequestParams.php');
require_once('lib/php/WikivoyagePageReader.php');
require_once('lib/php/BoundaryPageReader.php');
require_once('lib/php/GeoUtils.php');
require_once('lib/php/ArrayUtils.php');
require_once('lib/php/MonumentResult.php');
require_once('lib/php/WikimediaMapsReader.php');
require_once('lib/php/StringUtils.php');
require_once('lib/php/Api.php');

$api = new Api();
$api->processRequest();
