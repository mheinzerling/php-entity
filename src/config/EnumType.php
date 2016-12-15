<?php
namespace mheinzerling\entity\config;


class EnumType extends Type
{
    /**
     * @var Enum
     */
    private $enum;

    public function __construct(Enum $enum)
    {
        $this->enum = $enum;
    }

    public function toDatabaseType(int $length = null): \mheinzerling\commons\database\structure\type\Type
    {
        return new \mheinzerling\commons\database\structure\type\EnumType(array_keys($this->enum->getValues()));
    }
}