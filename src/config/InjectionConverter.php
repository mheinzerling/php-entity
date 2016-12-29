<?php
namespace mheinzerling\entity\config;

use mheinzerling\meta\language\AClass;
use mheinzerling\meta\language\ClassPHPType;
use mheinzerling\meta\language\PHPType;
use mheinzerling\meta\language\PrimitivePHPType;
use mheinzerling\meta\writer\MethodWriter;

class InjectionConverter
{


    public static function fixInjection(PHPType $type, string $fieldName, MethodWriter $methodWriter): void
    {
        if ($type instanceof EntityPHPType) {
            $primaries = array_keys($type->getEntity()->getPrimaryKeyDatabaseTypes());
            if (count($primaries) != 1) throw new \Exception("Not implemented for multiple or no primary keys");
            $primary = $primaries[0];
            $entity = $methodWriter->getClassWriter()->print($type->getClass());
            $methodWriter->line("if (!\$this->$fieldName instanceof " . $entity . " && \$this->$fieldName != null) {");
            $methodWriter->line("    \$pk = ['$primary' => intval(\$this->$fieldName)];");
            $methodWriter->line("    \$this->$fieldName = new $entity();");
            $methodWriter->line("    \$this->" . $fieldName . "->setPk(\$pk);");
            $methodWriter->line("}");
        } elseif ($type instanceof EnumPHPType) {
            $enum = $methodWriter->getClassWriter()->print($type->getClass());;
            $methodWriter->line("if (!\$this->$fieldName instanceof " . $enum . " && \$this->$fieldName != null) {");
            $methodWriter->line("    \$this->$fieldName = " . $enum . "::memberByValue(strtoupper(\$this->$fieldName));");
            $methodWriter->line("}");
        } elseif ($type instanceof ClassPHPType) {
            if ($type->getClass() == AClass::absolute(\DateTime::class)) {
                $methodWriter->line("if (!\$this->$fieldName instanceof \\DateTime && \$this->$fieldName != null) {");
                $methodWriter->line("    \$this->$fieldName = new \\DateTime(\$this->$fieldName);");
                $methodWriter->line("}");
            }
        } elseif ($type instanceof PrimitivePHPType) {
            if ($type->isInt()) {
                $methodWriter->line("\$this->$fieldName = intval(\$this->$fieldName);");
            } else if ($type->isBool()) {
                $methodWriter->line("\$this->$fieldName = \$this->$fieldName !== FALSE && \$this->$fieldName !== '0';");
            }
        }
    }
}
