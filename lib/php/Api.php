<?php

namespace WikivoyageApi;

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


class Api
{
    public function processRequest()
    {
        $requestParams = new RequestParams();

        $query = $requestParams->getQuery();

        if ($query === RequestParams::QUERY_GET_PAGE_DATA) {
            $this->processGetPageDataRequest($requestParams);
        } else if ($query === RequestParams::QUERY_LIST_PAGES) {
            $this->processListPagesRequest($requestParams);
        } else if ($query === RequestParams::QUERY_GET_WIKIDATA_BOUNDARIES) {
            $this->processGetWikidataBoundaries($requestParams);
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
    
    private function processListPagesRequest(RequestParams $requestParams)
    {
        $prefix = $requestParams->getPrefix();
        if (!$prefix) {
            $prefix = $requestParams->getPrefixParts() . ' (';
        }

        $wikivoyageApiUrl = 'https://ru.wikivoyage.org/w/api.php';
        $params = [
            'action' => 'query',
            'list' => 'allpages',
            'aplimit' => 'max',
            'apprefix' => $prefix,
            'format' => 'json'
        ];

        $result = [];

        $apcontinue = null;

        do {
            if (!is_null($apcontinue)) {
                $params['apcontinue'] = $apcontinue;
            }

            $encodedParams = $this->encodeUrlParams($params);
            $url = "{$wikivoyageApiUrl}?{$encodedParams}";

            $apiResponseStr = file_get_contents($url);
            $apiResponse = json_decode($apiResponseStr, true);

            foreach ($apiResponse['query']['allpages'] as $page) {
                $result[] = [
                    'title' => $page['title']
                ];
            }

            $apcontinue = isset($apiResponse['continue']['apcontinue']) ? $apiResponse['continue']['apcontinue'] : null;
        } while ($apcontinue !== null);

        $this->handleSuccess($result);
    }

    private function processGetWikidataBoundaries(RequestParams $requestParams)
    {
        $wikidataId = $requestParams->getWikidataId();
        $mapsReader = new WikimediaMapsReader();
        $polygons = $mapsReader->getPolygonsForWikidataIds([$wikidataId]);

        $this->handleSuccess(
            [
                'boundary-coordinates' => isset($polygons[$wikidataId]) ? $polygons[$wikidataId] : null
            ]
        );
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
            $districtid = isset($firstMonumentTitleData['districtid']) ? $firstMonumentTitleData['districtid'] : null;
        }

        return [
            'lat' => $lat,
            'long' => $long,
            'zoom' => $zoom,
            'districtid' => $districtid,
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

        return array_values(array_map(
            function(MonumentResult $resultItem) {
                return $resultItem->getResult();
            },
            $result
        ));
    }

    const BOUNDARY_NO = 'no';
    const MARKER_NO = 'no';

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
     * @param string $filtersStr
     * @return array
     */
    private function applyFilter($resultItems, $filtersStr)
    {
        $filters = is_null($filtersStr) ? [] : explode(',', $filtersStr);

        $filteredResultItems = $resultItems;

        foreach ($filters as $filter) {
            if ($filter === 'able-to-display-on-map') {
                $filteredResultItems = $this->filterAbleToDisplayOnMap($filteredResultItems);
            } else if ($filter === 'marker-enabled') {
                $filteredResultItems = $this->filterMarkerEnabled($filteredResultItems);
            }
        }

        return $filteredResultItems;
    }

    /**
     * @param MonumentResult[] $resultItems
     * @return MonumentResult[]
     */
    private function filterAbleToDisplayOnMap($resultItems)
    {
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
    }

    /**
     * @param MonumentResult[] $resultItems
     * @return MonumentResult[]
     */
    private function filterMarkerEnabled($resultItems)
    {
        return array_filter(
            $resultItems,
            function (MonumentResult $monument) {
                $marker = $monument->getMonumentField('marker');
                return $marker !== self::MARKER_NO;
            }
        );
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
