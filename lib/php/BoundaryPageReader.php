<?php

namespace WikivoyageApi;

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
