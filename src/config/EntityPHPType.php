<?php
declare(strict_types = 1);
namespace mheinzerling\entity\config;


use mheinzerling\commons\database\structure\type\Type;

class EntityPHPType extends PHPType
{
    /**
     * @var Entity
     */
    private $entity;

    public function __construct(Entity $entity)
    {
        $this->entity = $entity;
    }

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


}