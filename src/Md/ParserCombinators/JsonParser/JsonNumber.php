<?php

namespace Md\ParserCombinators\JsonParser;

class JsonNumber implements JsonValue
{
    private $value;
    public function __construct($value)
    {
        $this->value = $value;
    }

    public function __toString() { return "JsonNumber(" . $this->value . ")"; }
}