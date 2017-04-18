<?php

namespace Md\ParserCombinators\JsonParser;

class JsonArray implements JsonValue
{
    private $elements;

    public function __construct($elements)
    {
        $this->elements = $elements;
    }

    public function __toString()
    {
        return "JsonArray[\n" . implode(', ', array_map(function($el) { return (string) $el . "\n"; }, $this->elements)) . ']';
    }
}