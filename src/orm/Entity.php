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
        $entity = $repo->fetchByPk($this->getPk());
        foreach (get_object_vars($entity) as $key => $value) {
            $this->$key = $value;
        }
    }

    protected abstract function getPk(): array;

    protected abstract function setPk(array $pk): void;

    protected function load()
    {
        if ($this->loaded) return;
        $this->fetchFromDatabase();
        $this->loaded = true;
    }

}