<?php

error_reporting( E_ALL | E_STRICT );
ini_set( 'display_errors' , 1 );

require_once( '../../vendor/autoload.php' );

use Phayes\GeoPHP\GeoPHP;

$name = str_replace( "\'", "'", $_GET['name'] );
$name = str_replace( ' ', '_', $name );

$lang = $_GET['lang'];
$url = 'https://' . $lang . '.wikivoyage.org/w/api.php?action=query&prop=mapdata&format=json&titles=' . urlencode( $name );
$format = 'kml';
$response = \file_get_contents( $url );
$data = \json_decode( $response, true );
$pageData = \array_shift( $data['query']['pages'] );

$mapData = \json_decode( $pageData['mapdata'][0], true );
$geoJsonData = [];

foreach ( $mapData as $key => $collection ) {
	$geoJsonData[] = [
		'type' => 'FeatureCollection',
		'title' => $key,
		'features' => $collection,
	];
}

$geoJsonData = [
	'type' => 'FeatureCollection',
	'features' => $geoJsonData,
];

$geometry = GeoPHP::load( \json_encode( $geoJsonData ), 'geojson' );

$out = $geometry->out( $format );
header( 'Content-Length: ' . \strlen( $out ) );
header( 'Content-Type: application/vnd.google-earth.kml+xml' );
header( 'Content-Disposition: attachment; filename=' . $name . '.' . $format );
header( 'Content-Transfer-Encoding: binary' );
echo( $out );
