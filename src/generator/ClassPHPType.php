<?php
declare(strict_types = 1);
namespace mheinzerling\entity\generator;


use mheinzerling\commons\database\structure\type\DatetimeType;
use mheinzerling\commons\database\structure\type\Type;

class ClassPHPType extends PHPType
{
    /**
     * @var AClass
     */
    private $class;

    public function __construct(AClass $class)
    {
        $this->class = $class;
    }

    public function toPHPDoc(ClassWriter $classWriter): string
    {
        $doc = $this->class->write($classWriter);
        if ($this->optional) $doc .= "|null";
        return $doc;
    }

    public function toPHP(ClassWriter $classWriter): string
    {
        $type = '';
        if ($this->optional) $type .= "?";
        $type .= $this->class->write($classWriter);
        return $type;
    }


    public function toDatabaseType(int $length = null): Type
    {
        if ($this->class == AClass::of("\\" . \DateTime::class)) return new DatetimeType();
        throw new \Exception("Unhandled class " . $this->class->fullyQualified()); //TODO
    }

    public function fixInjection(string $fieldName, MethodWriter $methodWriter): void
    {
        parent::fixInjection($fieldName, $methodWriter);
        //TODO
        if ($this->class == AClass::of("\\" . \DateTime::class)) {
            $methodWriter->line("if (!\$this->$fieldName instanceof \\DateTime && \$this->$fieldName != null) {");
            $methodWriter->line("    \$this->$fieldName = new \\DateTime(\$this->$fieldName);");
            $methodWriter->line("}");
        }
    }

    public function toOptional(): PHPType
    {
        $type = new ClassPHPType($this->class);
        $type->setOptional(true);
        return $type;
    }


}