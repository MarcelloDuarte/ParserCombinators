<?php

namespace Md\ParserCombinators\JsonParser;

class JsonString implements JsonValue
{
    private $value;
    public function __construct($value)
    {
        $this->value = $value;
    }

    public function __toString() { return "JsonString(\"" . $this->value . "\")"; }
}