<?php

namespace mheinzerling\entity;

use mheinzerling\commons\database\structure\type\BoolType;
use mheinzerling\commons\database\structure\type\DatetimeType;
use mheinzerling\commons\database\structure\type\EnumType;
use mheinzerling\commons\database\structure\type\IntType;
use mheinzerling\commons\database\structure\type\Type;
use mheinzerling\commons\database\structure\type\VarcharType;
use mheinzerling\entity\config\EntityPHPType;
use mheinzerling\entity\config\EnumPHPType;
use mheinzerling\meta\language\AClass;
use mheinzerling\meta\language\ClassPHPType;
use mheinzerling\meta\language\PHPType;
use mheinzerling\meta\language\PrimitivePHPType;

class DatabaseTypeConverter
{
    public static function toDatabaseType(PHPType $type, int $length = null): Type
    {
        if ($type instanceof PrimitivePHPType) {
            if ($type->isBool()) return new BoolType();
            else if ($type->isInt()) return new IntType(); //TODO size , tiny big etc
            else if ($type->isString()) return new VarcharType($length); //TODO text etc
            else    throw new \Exception("Unhandled primitiv " . $type->getToken());

        } elseif ($type instanceof EntityPHPType) {
            $types = $type->getEntity()->getPrimaryKeyDatabaseTypes();
            if (count($types) > 1) throw new \Exception("Unsupported multi field foreign key");
            return reset($types);
        } elseif ($type instanceof EnumPHPType) {
            return new EnumType($type->getValues());
        } else if ($type instanceof ClassPHPType) {
            if ($type->getClass() == AClass::absolute(\DateTime::class)) return new DatetimeType();
            throw new \Exception("Unhandled class " . $type->getClass()->fullyQualified()); //TODO
        }
        throw new \Exception("Unhandled type " . get_class($type));
    }
}

