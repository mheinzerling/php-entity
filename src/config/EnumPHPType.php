<?php
declare(strict_types = 1);
namespace mheinzerling\entity\config;


use mheinzerling\commons\database\structure\type\EnumType;
use mheinzerling\commons\database\structure\type\Type;

class EnumPHPType extends PHPType
{
    /**
     * @var Enum
     */
    private $enum;

    public function __construct(Enum $enum)
    {
        $this->enum = $enum;
    }

    public function toDatabaseType(int $length = null): Type
    {
        return new EnumType(array_keys($this->enum->getValues()));
    }
}