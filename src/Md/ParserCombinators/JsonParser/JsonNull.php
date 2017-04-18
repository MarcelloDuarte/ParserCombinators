<?php

namespace Md\ParserCombinators\JsonParser;

class JsonNull implements JsonValue
{
    public function __toString() { return "JsonNull()"; }
}