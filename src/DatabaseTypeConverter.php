<?php
declare(strict_types = 1);

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
        if ($type instanceof PrimitivePHPType) return self::handlePrimitive($type, $length);
        if ($type instanceof ClassPHPType) return self::handleClass($type);
        throw new \Exception("Unhandled type " . get_class($type));
    }

    /**
     * @param PrimitivePHPType $type
     * @param int $length
     * @return BoolType|IntType|VarcharType|Type
     * @throws \Exception
     */
    private static function handlePrimitive(PrimitivePHPType $type, ?int $length): Type
    {
        if ($type->isBool()) return new BoolType();
        else if ($type->isInt()) return new IntType(); //TODO size , tiny big etc
        else if ($type->isString()) return new VarcharType($length, "utf8mb4_unicode_ci"); //TODO collation etc
        else throw new \Exception("Unhandled primitive " . $type->getToken());
    }

    /**
     * @param ClassPHPType $type
     * @return DatetimeType|EnumType|Type
     * @throws \Exception
     */
    private static function handleClass(ClassPHPType $type): Type
    {
        if ($type instanceof EnumPHPType) return new EnumType($type->getValues());
        if ($type instanceof EntityPHPType) {
            $types = $type->getEntity()->getPrimaryKeyDatabaseTypes();
            if (count($types) > 1) throw new \Exception("Unsupported multi field foreign key for " . $type->getEntity()->getName());
            return reset($types);
        }

        if ($type->getClass() == AClass::absolute(\DateTime::class)) return new DatetimeType();
        throw new \Exception("Unhandled class " . $type->getClass()->fullyQualified()); //TODO
    }
}

