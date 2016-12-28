<?php
declare(strict_types = 1);
namespace mheinzerling\entity\config;


use mheinzerling\commons\database\structure\type\Type;
use mheinzerling\entity\generator\ClassPHPType;
use mheinzerling\entity\generator\MethodWriter;

class EntityPHPType extends ClassPHPType
{
    /**
     * @var Entity
     */
    private $entity;


    public function __construct(Entity $entity)
    {
        parent::__construct($entity->getClass());
        $this->entity = $entity;
    }


    /** @noinspection PhpMissingParentCallCommonInspection
     * @param int $length
     * @return Type
     * @throws \Exception
     */
    public function toDatabaseType(int $length = null): Type
    {
        $types = $this->entity->getPrimaryKeyDatabaseTypes();
        if (count($types) > 1) throw new \Exception("Unsupported multi field foreign key");
        return reset($types);
    }

    /**
     * @return Entity
     */
    public function getEntity(): Entity
    {
        return $this->entity;
    }

    public function fixInjection(string $fieldName, MethodWriter $methodWriter): void
    {
        $primaries = array_keys($this->entity->getPrimaryKeyDatabaseTypes());
        if (count($primaries) != 1) throw new \Exception("Not implemented for multiple or no primary keys");
        $primary = $primaries[0];

        parent::fixInjection($fieldName, $methodWriter);


        $entity = $this->entity->getClass()->write($methodWriter->getClassWriter());
        $methodWriter->line("if (!\$this->$fieldName instanceof " . $entity . " && \$this->$fieldName != null) {");
        $methodWriter->line("    \$pk = ['$primary' => intval(\$this->$fieldName)];");
        $methodWriter->line("    \$this->$fieldName = new $entity();");
        $methodWriter->line("    \$this->" . $fieldName . "->setPk(\$pk);");
        $methodWriter->line("}");
    }


}