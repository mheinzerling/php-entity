<?php

namespace mheinzerling\entity;


use mheinzerling\commons\StringUtils;

class TypeUtil
{

    public static function isPrimitive($type)
    {
        return in_array($type, array("String", "Integer", "Boolean", "Double"));
    }

    public static function isExternalType($type)
    {
        return StringUtils::startsWith($type, "\\");
    }

    public static function isEntityOrEnum($type)
    {
        return !self::isPrimitive($type) && !self::isExternalType($type);
    }
}