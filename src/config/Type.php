<?php

namespace mheinzerling\entity\config;


abstract class Type
{

    public abstract function toDatabaseType(int $length = null): \mheinzerling\commons\database\structure\type\Type;

}