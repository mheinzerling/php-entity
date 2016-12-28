<?php
declare(strict_types = 1);

namespace mheinzerling\entity\generator;


use mheinzerling\commons\database\structure\type\BoolType;
use mheinzerling\commons\database\structure\type\IntType;
use mheinzerling\commons\database\structure\type\Type;
use mheinzerling\commons\database\structure\type\VarcharType;

class PrimitivePHPType extends PHPType
{
    /**
     * @var Primitive
     */
    private $type;

    function __construct(Primitive $type)
    {
        $this->type = $type;
    }

    public function toPHPDoc(ClassWriter $classWriter): string
    {
        $doc = $this->type->value(); //TODO array of Object
        if ($this->optional) $doc .= "|null";
        return $doc;
    }

    public function toPHP(ClassWriter $classWriter): string
    {
        $type = '';
        if ($this->optional) $type .= "?";
        $type .= $this->type->value();
        return $type;
    }

    public function toDatabaseType(int $length = null): Type
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

    public function getterPrefix(): string
    {
        if ($this->type == Primitive::BOOL()) return "is";
        return parent::getterPrefix();
    }

    public function toOptional(): PHPType
    {
        $type = new PrimitivePHPType($this->type);
        $type->setOptional(true);
        return $type;
    }

    public function fixInjection(string $fieldName, MethodWriter $methodWriter): void
    {
        parent::fixInjection($fieldName, $methodWriter);
        if ($this->type == Primitive::INT()) {
            $methodWriter->line("\$this->$fieldName = intval(\$this->$fieldName);");
        } else if ($this->type == Primitive::BOOL()) {
            $methodWriter->line("\$this->$fieldName = \$this->$fieldName !== FALSE && \$this->$fieldName !== '0';");
        }
    }

    public function toValue($value): string
    {
        if ($this->type == Primitive::INT()) {
            return $value;
        } else if ($this->type == Primitive::BOOL()) {
            if (empty($value)) return "false";
            else return "true";
        } else return parent::toValue($value);
    }


}