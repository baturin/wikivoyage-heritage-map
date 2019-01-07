<?php

namespace WikivoyageApi;

class RequestParams
{
    const QUERY_LIST_PAGES = 'list-pages';
    const QUERY_GET_PAGE_DATA = 'get-page-data';

    public function getQuery()
    {
        return isset($_GET['query']) ? $_GET['query'] : null;
    }

    public function getPage()
    {
        return isset($_GET['page']) ? $_GET['page'] : null;
    }

    public function getItems()
    {
        $items = isset($_GET['items']) ? $_GET['items'] : null;
        if ($items === null) {
            return [];
        } else {
            return explode(',', $items);
        }
    }

    public function getFields()
    {
        $fields = isset($_GET['fields']) ? $_GET['fields'] : null;
        if ($fields === null) {
            return [];
        } else {
            return explode(',', $fields);
        }
    }

    public function getFilter()
    {
        return isset($_GET['filter']) ? $_GET['filter'] : null;
    }
}

class WikivoyagePageReader
{
    public function read($page)
    {
        return @file_get_contents($this->getUrl($page));
    }

    private function getUrl($page)
    {
        return "https://ru.wikivoyage.org/w/index.php?title=" . $page . "&action=raw";
    }
}

class TemplateReader
{
    public function read($templateNames, $wikitext)
    {
        if (empty($templateNames)) {
            throw new \Exception('Template names list is empty');
        }

        $listingsData = [];

        foreach ($this->getTemplateWikitexts($templateNames, $wikitext) as $templateWikitext) {
            $listingsData[] = $this->parseTemplateWikitext($templateWikitext);
        }

        return $listingsData;
    }

    private function getTemplateWikitexts($templateNames, $wikitext)
    {
        $templateWikitexts = [];

        $templateNamesStr = implode(
            '|',
            array_map(function($templateName) {
                return preg_quote(strtolower($templateName), '/');
            }, $templateNames)
        );

        $matchResult = preg_match_all(
            '/\\{\\{\s*(' . $templateNamesStr . ')(\s|\\|)/i',
            $wikitext,
            $matches,
            PREG_OFFSET_CAPTURE
        );
        if ($matchResult) {
            foreach ($matches[1] as $match) {
                $position = $match[1];

                $braceCount = 2;
                $endIndex = null;

                for ($i = $position; $i < strlen($wikitext); $i++) {
                    $char = $wikitext[$i];
                    if ($char === '{') {
                        $braceCount++;
                    } elseif ($char === '}') {
                        $braceCount--;
                    }

                    if ($braceCount === 0) {
                        $endIndex = $i;
                        break;
                    }
                }

                if (!is_null($endIndex)) {
                    $templateWikitexts[] = substr($wikitext, $position, $endIndex - $position - 1);
                }
            }
        }

        return $templateWikitexts;
    }

    private function parseTemplateWikitext($wikitext)
    {
        $templateData = [];

        $templateItems = explode('|', $wikitext);
        array_shift($templateItems); // throw away template name itself

        foreach ($templateItems as $templateItem) {
            $items = explode('=', $templateItem, 2);
            $key = $items[0];
            $value = isset($items[1]) ? $items[1] : '';

            $key = trim($key);
            $value = trim($value);

            $templateData[$key] = $value;
        }

        return $templateData;
    }
}

class BoundaryPageReader
{
    public function read($pageName)
    {
        $wikivoyagePageReader = new WikivoyagePageReader();
        $pageContents = $wikivoyagePageReader->read($pageName);
        $pageContents = $this->stripNoinclude($pageContents);
        $data = json_decode($pageContents, true);
        if (is_array($data)) {
            return GeoUtils::swapLatLong($data);
        } else {
            return [];
        }
    }

    private function stripNoinclude($pageContents)
    {
        return preg_replace(
            '#' . preg_quote('<noinclude>') . '.*?' . preg_quote('</noinclude>') . '#ms',
            '',
            $pageContents
        );
    }
}


class GeoUtils
{
    public static function swapLatLong($data)
    {
        return array_map(function($item) {
            return [$item[1], $item[0]];
        }, $data);
    }
}

class ArrayUtils
{
    public static function getFirstNotNullValue($values, $defaultValue)
    {
        foreach ($values as $value) {
            if (!is_null($value)) {
                return $value;
            }
        }

        return $defaultValue;
    }

    public static function getNonEmptyStringValue($array, $key)
    {
        if (isset($array[$key])) {
            $value = (string)$array[$key];
            if ($value !== '') {
                return $value;
            } else {
                return null;
            }
        } else {
            return null;
        }
    }
}

class MonumentResult
{
    private $result;

    private $monumentType;

    private $monumentData;

    public function __construct($monumentType, $monumentData)
    {
        $this->monumentType = $monumentType;
        $this->monumentData = $monumentData;
        $this->result = [];
    }

    public function getMonumentType()
    {
        return $this->monumentType;
    }

    public function getMonumentField($fieldName)
    {
        return isset($this->monumentData[$fieldName]) ? $this->monumentData[$fieldName] : null;
    }

    public function getResultField($fieldName)
    {
        return isset($this->result[$fieldName]) ? $this->result[$fieldName] : null;
    }

    public function setResultField($fieldName, $value)
    {
        $this->result[$fieldName] = $value;
    }

    public function getResult()
    {
        return $this->result;
    }
}

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

function getImageStorageUrl($image) {
    if (is_null($image) || $image === '') {
        return null;
    }

    $image = str_replace(' ', '_', $image);

    if (substr($image,1,1) === '/') {
        return $image;
    }

    $md5 = md5($image);
    return substr($md5,0,1) . "/" . substr($md5,0,2) . "/" . $image;
}

function imageUrl($image)
{
    if (is_null($image) || $image === '') {
        return null;
    }

    return str_replace(' ', '_', $image);
}

class StringUtils
{
    public static function nullStr($str)
    {
        if ($str === null) {
            return '';
        } else {
            return (string)$str;
        }
    }
}

class Api
{
    public function processRequest()
    {
        $requestParams = new RequestParams();

        $query = $requestParams->getQuery();

        if ($query === RequestParams::QUERY_GET_PAGE_DATA) {
            $this->processGetPageDataRequest($requestParams);
        } else if ($query === RequestParams::QUERY_LIST_PAGES) {
            $this->handleError('Function is not implemented.');
        } else {
            $this->handleError('Please specify valid "query" parameter.');
        }
    }

    private function processGetPageDataRequest(RequestParams $requestParams)
    {
        $page = $requestParams->getPage();
        $wikivoyagePageReader = new WikivoyagePageReader();
        $pageContents = $wikivoyagePageReader->read($page);

        $items = $requestParams->getItems();

        $response = [];

        if (in_array('map-data', $items)) {
            $response['map-data'] = $this->getMapData($pageContents);
        }

        if (in_array('monuments', $items)) {
            $response['monuments'] = $this->getMonuments($requestParams, $pageContents);
        }

        $this->handleSuccess($response);
    }

    private function getMapData($pageContents)
    {
        $lat = null;
        $long = null;
        $zoom = null;

        $templateReader = new TemplateReader();
        $monumentTitleDatas = $templateReader->read(['monument-title', 'monument-title/nature'], $pageContents);

        if (isset($monumentTitleDatas[0])) {
            $firstMonumentTitleData = $monumentTitleDatas[0];
            $lat = (float)$firstMonumentTitleData['lat'];
            $long = (float)$firstMonumentTitleData['long'];
            $zoom = (int)$firstMonumentTitleData['zoom'];
        }

        return [
            'lat' => $lat,
            'long' => $long,
            'zoom' => $zoom
        ];
    }

    const MONUMENT_TYPE_CULTURAL = 'cultural';
    const MONUMENT_TYPE_NATURAL = 'natural';

    private function getMonuments(RequestParams $requestParams, $pageContents)
    {
        $templateReader = new TemplateReader();

        $result = [];

        $culturalMonuments = $templateReader->read(['monument'], $pageContents);
        foreach ($culturalMonuments as $monument) {
            $result[] = new MonumentResult(self::MONUMENT_TYPE_CULTURAL, $monument);
        }
        $naturalMonuments = $templateReader->read(['natural monument'], $pageContents);
        foreach ($naturalMonuments as $monument) {
            $result[] = new MonumentResult(self::MONUMENT_TYPE_NATURAL, $monument);
        }

        foreach ($result as $resultItem) {
            /** @var $resultItem MonumentResult */
            $monumentData = [];

            foreach ($requestParams->getFields() as $field) {
                $resultItem->setResultField(
                    $field,
                    $resultItem->getMonumentField($field)
                );
            }

            if (in_array('upload-link', $requestParams->getFields())) {
                $this->readUploadLink($resultItem);
            }
        }

        if (in_array('boundary-coordinates', $requestParams->getFields())) {
            $this->readBoundaryCoordinates($result);
        }

        if (in_array('image-thumb-120px', $requestParams->getFields())) {
            foreach ($result as $resultItem) {
                $image = $resultItem->getMonumentField('image');
                $storageUrl = getImageStorageUrl($resultItem->getMonumentField('image'));

                if (!is_null($storageUrl)) {
                    $resultItem->setResultField(
                        'image-thumb-120px',
                        'https://upload.wikimedia.org/wikipedia/commons/thumb/' . $storageUrl . '/120px-' . imageUrl($image)
                    );
                } else {
                    $resultItem->setResultField(
                        'image-thumb-120px',
                        null
                    );
                }
            }
        }

        if (in_array('image-page-url', $requestParams->getFields())) {
            foreach ($result as $resultItem) {
                $image = $resultItem->getMonumentField('image');

                if (!is_null($image) && $image !== '') {
                    $imagePageUrl = 'https://ru.m.wikivoyage.org/wiki/File:' . imageUrl($image);
                } else {
                    $imagePageUrl = null;
                }

                $resultItem->setResultField(
                    'image-page-url',
                    $imagePageUrl
                );
            }
        }

        $result = $this->applyFilter($result, $requestParams->getFilter());

        return array_map(
            function(MonumentResult $resultItem) {
                return $resultItem->getResult();
            },
            $result
        );
    }

    const BOUNDARY_NO = 'no';

    /**
     * @param MonumentResult[] $resultItems
     */
    private function readBoundaryCoordinates($resultItems)
    {
        foreach ($resultItems as $resultItem) {
            $resultItem->setResultField('boundary-coordinates', []);
        }

        foreach ($resultItems as $resultItem) {
            $boundary = $resultItem->getMonumentField('boundary');

            if ($boundary === null || $boundary === '' || $boundary === self::BOUNDARY_NO) {
                continue;
            }

            $boundaryPageReader = new BoundaryPageReader();
            $boundaryData = $boundaryPageReader->read($boundary);
            $resultItem->setResultField(
                'boundary-coordinates',
                array_merge($resultItem->getResultField('boundary-coordinates'), [$boundaryData])
            );
        }

        $wikidataIds = [];

        foreach ($resultItems as $resultItem) {
            $boundary = $resultItem->getMonumentField('boundary');

            if ($boundary !== null && $boundary !== '') {
                // coordinates are loaded from boundary page on Wikivoyage, we don't need to load them from OSM
                continue;
            }

            $wdid = $resultItem->getMonumentField('wdid');
            if ($wdid !== null && $wdid !== '') {
                $wikidataIds[] = $wdid;
            }
        }

        $resultItemsByWdid = [];
        foreach ($resultItems as $resultItem) {
            $wdid = $resultItem->getMonumentField('wdid');

            if ($wdid !== null && $wdid !== '') {
                if (!isset($resultItemsByWdid[$wdid])) {
                    $resultItemsByWdid[$wdid] = [];
                }
                $resultItemsByWdid[$wdid][] = $resultItem;
            }
        }

        $wikimediaMapsReader = new WikimediaMapsReader();

        $wikidataBoundaries = $wikimediaMapsReader->getPolygonsForWikidataIds($wikidataIds);
        foreach ($wikidataBoundaries as $wdid => $boundaryInfo) {
            /** @var MonumentResult $resultItem */
            foreach ($resultItemsByWdid[$wdid] as $resultItem) {
                $boundary = $resultItem->getMonumentField('boundary');

                if ($boundary !== null && $boundary !== '') {
                    // coordinates are loaded from boundary page on Wikivoyage, we don't need to load them from OSM
                    continue;
                }

                $resultItem->setResultField(
                    'boundary-coordinates',
                    array_merge($resultItem->getResultField('boundary-coordinates'), $boundaryInfo)
                );
            }
        }
    }

    const NOUPLOAD_YES = 'yes';

    const CAMPAIGN_WLM_RU = 'wlm-ru';
    const CAMPAIGN_WLM_CRIMEA = 'wlm-crimea';
    const CAMPAIGN_WLE_RU = 'wle-ru';
    const CAMPAIGN_WLE_CRIMEA = 'wle-crimea';

    private function getUploadCampaign(MonumentResult $monumentResult)
    {
        $campaign = null;

        if ($monumentResult->getMonumentType() === self::MONUMENT_TYPE_CULTURAL) {
            $region = StringUtils::nullStr($monumentResult->getMonumentField('region'));

            $campaign = self::CAMPAIGN_WLM_RU;
            if ($region === 'ru-km' || $region === 'ru-sev') {
                $campaign = self::CAMPAIGN_WLM_CRIMEA;
            }
        } else if ($monumentResult->getMonumentType() === self::MONUMENT_TYPE_NATURAL) {
            $knid = StringUtils::nullStr($monumentResult->getMonumentField('knid'));

            $campaign = self::CAMPAIGN_WLE_RU;
            if (substr($knid, 0, 2) == '82' || substr($knid, 0, 2) == '92') {
                $campaign = self::CAMPAIGN_WLE_CRIMEA;
            }
        }

        return $campaign;
    }

    private function readUploadLink(MonumentResult $monumentResult)
    {
        $noupload = $monumentResult->getMonumentField('noupload');
        if ($noupload === self::NOUPLOAD_YES) {
            $uploadLink = null;
        } else {
            $region = StringUtils::nullStr($monumentResult->getMonumentField('region'));
            $name = StringUtils::nullStr($monumentResult->getMonumentField('name'));
            $knid = StringUtils::nullStr($monumentResult->getMonumentField('knid'));
            $uid = StringUtils::nullStr($monumentResult->getMonumentField('uid'));
            $address = StringUtils::nullStr($monumentResult->getMonumentField('address'));
            $municipality = StringUtils::nullStr($monumentResult->getMonumentField('municipality'));
            $district = StringUtils::nullStr($monumentResult->getMonumentField('district'));
            $commonscat = StringUtils::nullStr($monumentResult->getMonumentField('commonscat'));

            $uploadCampaign = $this->getUploadCampaign($monumentResult);

            if ($uploadCampaign !== null) {
                $uploadDesc = str_replace('"', '', $name) . ': ';
                if ($address !== '') {
                    $uploadDesc .= $address . ', ';
                }

                if ($municipality !== '' && $municipality !== $district) {
                    $uploadDesc .= $municipality . ', ';
                }

                if ($district !== '') {
                    $uploadDesc .= $district . ', ';
                }

                $uploadDesc .= StringUtils::nullStr($this->getRegionName($region));

                $urlParameters = [
                    'title' => 'Special:UploadWizard',
                    'campaign' => $uploadCampaign,
                    'id' => $knid,
                    'id2' => $uid,
                    'description' => $uploadDesc,
                    'categories' => $commonscat,
                    'uselang' => 'ru',

                ];

                $uploadLink = 'http://commons.wikimedia.org/w/index.php?' . $this->encodeUrlParams($urlParameters);
            } else {
                $uploadLink = null;
            }
        }

        $monumentResult->setResultField('upload-link', $uploadLink);
    }

    private function encodeUrlParams($params)
    {
        $items = [];
        foreach ($params as $k => $v) {
            $items[] = $k . '=' . urlencode($v);
        }
        return implode('&', $items);
    }

    private function getRegionName($regionCode)
    {
        $regionNames = [
            "ru-ad" => "Адыгея",
            'ru-ba' => 'Башкортостан',
            'ru-bu' => 'Бурятия',
            'ru-al' => 'Алтай',
            'ru-da' => 'Дагестан',
            'ru-in' => 'Ингушетия',
            'ru-kb' => 'Кабардино-Балкария',
            'ru-kl' => 'Калмыкия',
            'ru-kc' => 'Карачаево-Черкесия',
            'ru-krl' => 'Карелия',
            'ru-ko' => 'Республика Коми',
            'ru-me' => 'Марий Эл',
            'ru-mo' => 'Мордовия',
            'ru-sa' => 'Якутия (Саха)',
            'ru-se' => 'Северная Осетия',
            'ru-ta' => 'Татарстан',
            'ru-ty' => 'Тува',
            'ru-ud' => 'Удмуртия',
            'ru-kk' => 'Хакасия',
            'ru-ce' => 'Чеченская республика',
            'ru-chv' => 'Чувашия',
            'ru-alt' => 'Алтайский край',
            'ru-kda' => 'Краснодарский край',
            'ru-kya' => 'Красноярский край',
            'ru-pri' => 'Приморский край',
            'ru-sta' => 'Ставропольский край',
            'ru-kha' => 'Хабаровский край',
            'ru-amu' => 'Амурская область',
            'ru-ark' => 'Архангельская область',
            'ru-ast' => 'Астраханская область',
            'ru-bel' => 'Белгородская область',
            'ru-bry' => 'Брянская область',
            'ru-vla' => 'Владимирская область',
            'ru-vgg' => 'Волгоградская область',
            'ru-vol' => 'Вологодская область',
            'ru-vor' => 'Воронежская область',
            'ru-iva' => 'Ивановская область',
            'ru-irk' => 'Иркутская область',
            'ru-kal' => 'Калининградская область',
            'ru-klu' => 'Калужская область',
            'ru-kam' => 'Камчатский край',
            'ru-kem' => 'Кемеровская область',
            'ru-kir' => 'Кировская область',
            'ru-kos' => 'Костромская область',
            'ru-kgn' => 'Курганская область',
            'ru-krs' => 'Курская область',
            'ru-len' => 'Ленинградская область',
            'ru-lip' => 'Липецкая область',
            'ru-mag' => 'Магаданская область',
            'ru-mos' => 'Московская область',
            'ru-mur' => 'Мурманская область',
            'ru-niz' => 'Нижегородская область',
            'ru-ngr' => 'Новгородская область',
            'ru-nvs' => 'Новосибирская область',
            'ru-oms' => 'Омская область',
            'ru-ore' => 'Оренбургская область',
            'ru-orl' => 'Орловская область',
            'ru-pnz' => 'Пензенская область',
            'ru-per' => 'Пермский край',
            'ru-psk' => 'Псковская область',
            'ru-ros' => 'Ростовская область',
            'ru-rya' => 'Рязанская область',
            'ru-sam' => 'Самарская область',
            'ru-sar' => 'Саратовская область',
            'ru-sak' => 'Сахалинская область',
            'ru-sve' => 'Свердловская область',
            'ru-smo' => 'Смоленская область',
            'ru-tam' => 'Тамбовская область',
            'ru-tve' => 'Тверская область',
            'ru-tom' => 'Томская область',
            'ru-tul' => 'Тульская область',
            'ru-tyu' => 'Тюменская область',
            'ru-uly' => 'Ульяновская область',
            'ru-che' => 'Челябинская область',
            'ru-zab' => 'Забайкальский край',
            'ru-yar' => 'Ярославская область',
            'ru-mow' => 'Москва',
            'ru-spb' => 'Санкт-Петербург',
            'ru-jew' => 'Еврейская автономная область',
            'ru-km' => 'Крым',
            'ru-nen' => 'Ненецкий автономный округ',
            'ru-khm' => 'Ханты-Мансийский автономный округ',
            'ru-chu' => 'Чукотский автономный округ',
            'ru-yam' => 'Ямало-Ненецкий автономный округ',
            'ru-sev' => 'Севастополь',
        ];

        return array_key_exists($regionCode, $regionNames) ? $regionNames[$regionCode] : null;
    }

    /**
     * @param MonumentResult[] $resultItems
     * @param string $filter
     * @return array
     */
    private function applyFilter($resultItems, $filter)
    {
        if ($filter === 'able-to-display-on-map') {
            return array_filter(
                $resultItems,
                function (MonumentResult $monument) {
                    $lat = $monument->getMonumentField('lat');
                    $long = $monument->getMonumentField('long');
                    $boundaryCoordinates = $monument->getResultField('boundary-coordinates');

                    return (
                        (
                            $lat !== null && $lat !== '' && $long !== null && $long !== ''
                        ) || (
                            is_array($boundaryCoordinates) && !empty($boundaryCoordinates)
                        )
                    );
                }
            );
        } else {
            return $resultItems;
        }
    }

    private function handleError($errorMessage)
    {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'errorMessage' => $errorMessage
        ]);
    }

    private function handleSuccess($data)
    {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => $data
        ]);
    }

    private function jsonEncode($data)
    {
        return json_encode($data);
    }
}

$api = new Api();
$api->processRequest();
