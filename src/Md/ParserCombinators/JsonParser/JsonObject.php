<?php

namespace Md\ParserCombinators\JsonParser;

use Phunkie\Types\ImmMap;

class JsonObject implements JsonValue
{
    private $pairs = [];
    public function __construct(ImmMap $pairs)
    {
        $this->pairs = $pairs;
    }

    public function __toString()
    {
        $object = [];
        foreach ($this->pairs->iterator() as $key => $value) {
            $object []= "$key:$value";
        }
        return 'JsonObject{' . implode(',', $object) . '}';
    }
}