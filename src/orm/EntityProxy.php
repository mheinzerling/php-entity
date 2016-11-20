<?php


namespace mheinzerling\entity\orm;


use mheinzerling\commons\StringUtils;

class EntityProxy extends Entity
{
    /**
     * @var Entity
     */
    private $entity;
    /**
     * @var string
     */
    private $repo;
    private $pk;

    public function __construct(string $repo, array $pk)
    {
        $this->repo = $repo;
        $this->pk = $pk;
    }

    private function createRepo(): EntityRepository
    {
        return new $this->repo();
    }

    public function __call(string $functionName, array $parameters = null)
    {
        if ($this->entity == null) {
            $repo = $this->createRepo();
            $pkName = $repo->meta->pk[0];
            $pkGetter = 'get' . StringUtils::firstCharToUpper($pkName); //TODO guard
            if ($functionName == $pkGetter) {
                return $this->pk[$pkName];
            }
            $values = array_values($this->pk);
            $this->entity = $repo->fetchByPk($values[0]); //TODO composed pk
        }
        return call_user_func_array([$this->entity, $functionName], $parameters);
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    public function this() : Entity
    {
        if ($this->entity == null) {
            $repo = $this->createRepo();
            $values = array_values($this->pk);
            $this->entity = $repo->fetchByPk($values[0]); //TODO composed pk
        }
        return $this->entity;
    }
}