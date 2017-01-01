<?php
declare(strict_types = 1);

namespace mheinzerling\entity;

use mheinzerling\commons\database\structure\type\BoolType;
use mheinzerling\commons\database\structure\type\DatetimeType;
use mheinzerling\commons\database\structure\type\EnumType;
use mheinzerling\commons\database\structure\type\IntType;
use mheinzerling\commons\database\structure\type\VarcharType;
use mheinzerling\entity\config\Config;
use mheinzerling\entity\config\EntityPHPType;
use mheinzerling\entity\config\EnumPHPType;
use mheinzerling\meta\language\AClass;
use mheinzerling\meta\language\ClassPHPType;
use mheinzerling\meta\language\PHPType;
use mheinzerling\meta\language\Primitive;
use mheinzerling\meta\language\PrimitivePHPType;

class DatabaseTypeConverterTest extends \PHPUnit_Framework_TestCase
{

    public function testPrimitive()
    {
        static::assertEquals(new BoolType(), DatabaseTypeConverter::toDatabaseType(new PrimitivePHPType(Primitive::BOOL())));
        static::assertEquals(new IntType(), DatabaseTypeConverter::toDatabaseType(new PrimitivePHPType(Primitive::INT())));
        static::assertEquals(new VarcharType(null, 'utf8mb4_unicode_ci'), DatabaseTypeConverter::toDatabaseType(new PrimitivePHPType(Primitive::STRING())));
        try {
            DatabaseTypeConverter::toDatabaseType(new PrimitivePHPType(Primitive::ARRAY()));
            static::fail("Exception expected");
        } catch (\Exception $e) {
            static::assertEquals("Unhandled primitive array", $e->getMessage());
        }
    }

    public function testClass()
    {
        $config = Config::loadFile(realpath(__DIR__ . "/..") . "/resources/tests/entities.json");

        static::assertEquals(new EnumType(['m', 'f']), DatabaseTypeConverter::toDatabaseType(new EnumPHPType($config->getEnums()["Gender"])));
        static::assertEquals(new IntType(), DatabaseTypeConverter::toDatabaseType(new EntityPHPType($config->getEntities()["User"])));
        try {
            DatabaseTypeConverter::toDatabaseType(new EntityPHPType($config->getEntities()["Credential"]));
            static::fail("Exception expected");
        } catch (\Exception $e) {
            static::assertEquals("Unsupported multi field foreign key for Credential", $e->getMessage());
        }
        static::assertEquals(new DatetimeType(), DatabaseTypeConverter::toDatabaseType(new ClassPHPType(AClass::absolute(\DateTime::class))));

        try {
            DatabaseTypeConverter::toDatabaseType(new ClassPHPType(AClass::absolute(\PDO::class)));
            static::fail("Exception expected");
        } catch (\Exception $e) {
            static::assertEquals("Unhandled class \\PDO", $e->getMessage());
        }

        try {
            DatabaseTypeConverter::toDatabaseType(new class extends PHPType
            {
                public function toOptional(): PHPType
                {
                    return new PrimitivePHPType(Primitive::BOOL());
                }
            });
            static::fail("Exception expected");
        } catch (\Exception $e) {
            static::assertEquals("Unhandled type class@anonymous", substr($e->getMessage(), 0, 30));
        }
    }
}