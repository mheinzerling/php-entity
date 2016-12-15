<?php

namespace mheinzerling\entity\config;


use mheinzerling\commons\database\structure\type\BoolType;
use mheinzerling\commons\database\structure\type\IntType;
use mheinzerling\commons\database\structure\type\VarcharType;

class PrimitiveType extends Type
{
    /**
     * @var Primitive
     */
    private $type;

    function __construct(Primitive $type)
    {
        $this->type = $type;
    }


    public function toDatabaseType(int $length = null): \mheinzerling\commons\database\structure\type\Type
    {
        switch ($this->type) {
            case Primitive::BOOL():
                return new BoolType();
            case Primitive::INT():
                return new IntType(); //TODO size , tiny big etc
            case Primitive::STRING():
                return new VarcharType($length); //TODO text etc
            default:
                throw new \Exception("Unhandled primitiv " . $this->type);
        }
    }
}