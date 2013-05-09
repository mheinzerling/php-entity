<?php

namespace mheinzerling\entity;


use mheinzerling\commons\StringUtils;
use mheinzerling\commons\database\DatabaseUtils;

abstract class EntityRepository
{
    private $connection;
    public $meta; //TODO

    public function __construct(\PDO $connection = null)
    {
        $this->connection = $connection == null ? PersistenceProvider::getConnection() : $connection;
        $this->meta = new EntityMetaData($this);

    }

    public function initialize()
    {
        return $this->connection->exec($this->meta->dropSchema() . $this->meta->buildSchema());
    }

    public function persist(Entity &$entity)
    {
        $data = array();
        foreach ($this->meta->fields as $key => $field) {
            $method = "get" . StringUtils::firstCharToUpper($key);
            $data[$key] = $this->mapValue($entity->$method());
        }
        if ($this->meta->autoincrement != null && $data[$this->meta->autoincrement] == null) {
            DatabaseUtils::insertAssoc($this->connection, $this->meta->table, $data);
            $method = "set" . StringUtils::firstCharToUpper($this->meta->autoincrement);
            $entity->$method($this->connection->lastInsertId());
        } else {
            DatabaseUtils::insertOrUpdateAssoc($this->connection, $this->meta->table, $data);
        }
    }

    private function mapValue($value)
    {
        if (is_string($value) || is_numeric($value) || is_null($value)) return $value;
        if (is_object($value)) {

            if (is_subclass_of($value, '\mheinzerling\entity\Entity')) {
                $repoClass = get_class($value) . "Repository";
                $meta = new EntityMetaData(new $repoClass());
                $pk = $meta->pk;
                if (count($pk) != 1) throw new \Exception("Can't map foreign key to composed primary keys :" . implode(',', $pk)); //TODO
                $method = "get" . StringUtils::firstCharToUpper($pk[0]);
                return $value->$method();
            } elseif ($value instanceof \DateTime) {
                return $value->format("Y-m-d H:i:s");
            } else {
                throw new \Exception("Missing database mapping for type :" . get_class($value)); //TODO
            }

        }
        throw new \Exception("Missing database mapping for type :" . gettype($value)); //TODO

    }

    public abstract function getMeta();

    /**
     * @return \PDOStatement
     */
    private function prepareStatement($constraint)
    {
        if ($constraint == null) {
            $constraint = '';
        } else {
            //TODO sanitize
            $constraint = ' ' . $constraint;
        }
        return $this->connection->query('SELECT * FROM `' . $this->meta->table . '`' . $constraint);
    }

    public function fetchAll($constraint = null)
    {
        $stmt = $this->prepareStatement($constraint);
        return $stmt->fetchAll(\PDO::FETCH_CLASS, $this->meta->entityClass);
    }

    public function fetchUnique($constraint = null)
    {
        $stmt = $this->prepareStatement($constraint);
        $stmt->setFetchMode(\PDO::FETCH_CLASS, $this->meta->entityClass);
        return $stmt->fetch();
    }

    public function fetchByPk($pk)
    {
        if (is_array($pk) || count($this->meta->pk) != 1) throw new \Exception("Unsupported operation");
        $key = $this->meta->pk[0];
        return $this->fetchUnique("WHERE `" . $key . "`='" . $pk . "'"); //TODO
    }


}