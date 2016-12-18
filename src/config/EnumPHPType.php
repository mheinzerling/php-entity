<?php
declare(strict_types = 1);
namespace mheinzerling\entity\config;


use mheinzerling\commons\database\structure\type\EnumType;
use mheinzerling\commons\database\structure\type\Type;
use mheinzerling\entity\generator\ClassPHPType;
use mheinzerling\entity\generator\MethodWriter;

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

    /** @noinspection PhpMissingParentCallCommonInspection
     * @param int $length
     * @return Type
     */
    public function toDatabaseType(int $length = null): Type
    {
        return new EnumType(array_keys($this->enum->getValues()));
    }

    public function fixInjection(string $fieldName, MethodWriter $methodWriter): void
    {
        parent::fixInjection($fieldName, $methodWriter);
        $enum = $this->enum->getClass()->write($methodWriter->getClassWriter());
        $methodWriter->line("if (!\$this->$fieldName instanceof " . $enum . " && \$this->$fieldName != null) {");
        $methodWriter->line("    \$this->$fieldName = " . $enum . "::memberByValue(strtoupper(\$this->$fieldName));");
        $methodWriter->line("}");
    }

}