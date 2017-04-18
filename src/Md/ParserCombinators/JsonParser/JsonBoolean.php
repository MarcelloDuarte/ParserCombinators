<?php

namespace Md\ParserCombinators\JsonParser;

class JsonBoolean implements JsonValue
{
    private $value;
    public function __construct($value)
    {
        if ($value === true || $value === false) {
            $this->value = $value;
            return;
        }
        throw new \InvalidArgumentException('JsonBoolean accepts true or false only');
    }

    public function __toString() { return "JsonBoolean(" . ($this->value ? 'true' : 'false') . ")"; }
}