<?php
namespace mheinzerling\entity\config;


use mheinzerling\commons\database\structure\type\DatetimeType;

class ClassType extends Type
{
    /**
     * @var string
     */
    private $class;

    public function __construct(string $class)
    {
        $this->class = $class;
    }


    public function toDatabaseType(int $length = null): \mheinzerling\commons\database\structure\type\Type
    {

        if (trim($this->class, "\\") == \DateTime::class) return new DatetimeType();
        throw new \Exception("Unhandled class " . $this->class); //TODO
    }
}