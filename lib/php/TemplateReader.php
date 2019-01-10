<?php

namespace WikivoyageApi;

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
