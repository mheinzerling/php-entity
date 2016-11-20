<?php

namespace mheinzerling\entity;


use mheinzerling\commons\StringUtils;

class TypeUtil
{

    public static function isPrimitive(string $type):bool
    {
        return in_array($type, ["String", "Integer", "Boolean", "Double"]);
    }

    public static function isExternalType(string $type):bool
    {
        return StringUtils::startsWith($type, "\\");
    }

    public static function isEntityOrEnum(string $type):bool
    {
        return !self::isPrimitive($type) && !self::isExternalType($type);
    }
}