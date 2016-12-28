<?php
declare(strict_types = 1);

namespace mheinzerling\entity\generator;

use mheinzerling\commons\database\structure\type\Type;

abstract class PHPType
{
    /**
     * @var bool
     */
    protected $optional = false;

    public abstract function toDatabaseType(int $length = null): Type;

    public abstract function toPHPDoc(ClassWriter $classWriter): string;

    public abstract function toPHP(ClassWriter $classWriter): string;


    public function isOptional(): bool
    {
        return $this->optional;
    }


    public function setOptional(bool $optional): void
    {
        $this->optional = $optional;
    }

    public abstract function toOptional(): PHPType;

    public function fixInjection(string $fieldName, MethodWriter $methodWriter): void
    {
        // do nothing
    }

    public function getterPrefix(): string
    {
        return 'get';
    }

    public function toValue($value): string
    {
        return "'" . $value . "'";
    }
}