<?php

namespace WikivoyageApi;

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