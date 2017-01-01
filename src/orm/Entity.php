<?php
declare(strict_types = 1);

namespace mheinzerling\entity\orm;

abstract class Entity
{
    protected $loaded = true;

    protected function __construct()
    {
    }

    protected function fetchFromDatabase()
    {
        /**
         * @var $repo EntityRepository
         */
        $repo = (new \ReflectionClass(get_class($this) . "Repository"))->newInstance();
        $entity = $repo->fetchByPk($this->getPrimary());
        foreach (get_object_vars($entity) as $key => $value) {
            $this->$key = $value;
        }
    }

    protected abstract function getPrimary(): array;

    protected abstract function setPrimary(array $primary): void;

    protected function load()
    {
        if ($this->loaded) return;
        $this->fetchFromDatabase();
        $this->loaded = true;
    }

}