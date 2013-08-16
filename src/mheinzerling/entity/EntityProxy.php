<?php


namespace mheinzerling\entity;


use mheinzerling\commons\StringUtils;

class EntityProxy extends Entity
{

    private $entity;
    private $repo;
    private $pk;

    public function __construct($repo, array $pk)
    {
        $this->repo = $repo;
        $this->pk = $pk;
    }

    /**
     * @return EntityRepository
     */
    private function createRepo()
    {
        return new $this->repo();
    }

    public function __call($function_name, $parameters)
    {
        if ($this->entity == null) {
            $r = $this->createRepo();
            $pkName = $r->meta->pk[0];
            $pkGetter = 'get' . StringUtils::firstCharToUpper($pkName); //TODO guard
            if ($function_name == $pkGetter) {
                return $this->pk[$pkName];
            }
            $values = array_values($this->pk);
            $this->entity = $r->fetchByPk($values[0]); //TODO composed pk
        }
        return call_user_func_array(array($this->entity, $function_name), $parameters);
    }

    public function this()
    {
        if ($this->entity == null) {
            $r = $this->createRepo();
            $values = array_values($this->pk);
            $this->entity = $r->fetchByPk($values[0]); //TODO composed pk
        }
        return $this->entity;
    }
}