<?php
declare(strict_types = 1);
namespace mheinzerling\entity\config;


use mheinzerling\meta\language\ClassPHPType;

class EnumPHPType extends ClassPHPType
{
    /**
     * @var Enum
     */
    private $enum;

    public function __construct(Enum $enum)
    {
        parent::__construct($enum->getClass());
        $this->enum = $enum;
    }

    /**
     * @return string[]
     */
    public function getValues(): array
    {
        return array_keys($this->enum->getValues());
    }
}