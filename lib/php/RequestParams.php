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

    public function getPrefix()
    {
        return isset($_GET['prefix']) ? $_GET['prefix'] : null;
    }

    public function getPrefixParts()
    {
        return isset($_GET['prefix-parts']) ? $_GET['prefix-parts'] : null;
    }
}
