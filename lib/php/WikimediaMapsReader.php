<?php

namespace WikivoyageApi;

class WikimediaMapsReader
{
    /**
     * @param string[] $wikidataIds
     * @return array|null
     */
    public function getPolygonsForWikidataIds($wikidataIds)
    {
        $result = null;

        if (count($wikidataIds) > 0) {
            $url = 'https://maps.wikimedia.org/geoshape?getgeojson=1&ids=' . implode(',', $wikidataIds);
            $geoJsonStr = file_get_contents($url);
            $geoJsonData = json_decode($geoJsonStr, true);

            foreach ($geoJsonData['features'] as $item) {
                $wdid = $item['id'];
                $polygons = $item['geometry']['coordinates'];

                if ($item['geometry']['type'] === 'MultiPolygon') {
                    $result[$wdid] = [];
                    foreach ($polygons as $polygon1) {
                        foreach ($polygon1 as $polygon2) {
                            $result[$wdid][] = GeoUtils::swapLatLong($polygon2);
                        }
                    }
                }
            }
        }

        return $result;
    }
}