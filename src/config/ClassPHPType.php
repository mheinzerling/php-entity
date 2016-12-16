<?php
declare(strict_types = 1);
namespace mheinzerling\entity\config;


use mheinzerling\commons\database\structure\type\DatetimeType;
use mheinzerling\commons\database\structure\type\Type;

class ClassPHPType extends PHPType
{
    /**
     * @var string
     */
    private $class;

    public function __construct(string $class)
    {
        $this->class = $class;
    }


    public function toDatabaseType(int $length = null): Type
    {

        if (trim($this->class, "\\") == \DateTime::class) return new DatetimeType();
        throw new \Exception("Unhandled class " . $this->class); //TODO
    }
}