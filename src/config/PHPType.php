<?php
declare(strict_types = 1);

namespace mheinzerling\entity\config;


use mheinzerling\commons\database\structure\type\Type;

abstract class PHPType
{

    public abstract function toDatabaseType(int $length = null): Type;

}