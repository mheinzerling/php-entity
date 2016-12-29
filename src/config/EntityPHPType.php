<?php
declare(strict_types = 1);
namespace mheinzerling\entity\config;


use mheinzerling\meta\language\ClassPHPType;

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

    /**
     * @return Entity
     */
    public function getEntity(): Entity
    {
        return $this->entity;
    }
}