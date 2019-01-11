<?php

namespace WikivoyageApi;

class WikivoyagePageReader
{
    public function read($page)
    {
        return @file_get_contents($this->getUrl($page));
    }

    private function getUrl($page)
    {
        $page = str_replace(' ', '_', $page);
        return "https://ru.wikivoyage.org/w/index.php?title=" . $page . "&action=raw";
    }
}
