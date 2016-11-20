<?php

namespace mheinzerling\entity;


use mheinzerling\commons\database\ConnectionProvider;
use mheinzerling\commons\database\DatabaseUtils;
use mheinzerling\commons\StringUtils;

abstract class EntityRepository
{
    /**
     * @var \PDO
     */
    private $connection;
    /**
     * @var EntityMetaData
     */
    public $meta; //TODO

    public function __construct(\PDO $connection = null)
    {
        $this->connection = $connection == null ? ConnectionProvider::get() : $connection;
        $this->meta = new EntityMetaData($this);

    }


    public function initialize():int
    {
        return $this->connection->exec($this->meta->dropSchema() . $this->meta->buildSchema());
    }

    public function isInitialized():bool
    {
        $query = "SHOW TABLES LIKE '" . $this->meta->table . "'";
        return $this->connection->query($query)->rowCount() == 1;
    }

    /**
     * @param Entity $entity
     * @return void
     */
    public function persist(Entity &$entity)
    {
        $data = [];
        foreach ($this->meta->fields as $key => $field) {
            $method = "get" . StringUtils::firstCharToUpper($key);
            $data[$key] = $this->mapValue($key, $entity->$method());
            if ($data[$key] == null) {
                $mandatory = !(isset($field['optional']) && $field['optional']);
                $default = isset($field['default']);
                if ($mandatory && $default) {
                    $data[$key] = $field['default'];
                    $method = "set" . StringUtils::firstCharToUpper($key);
                    $entity->$method($data[$key]);
                }
            }
        }
        if ($this->meta->autoincrement != null && $data[$this->meta->autoincrement] == null) {
            DatabaseUtils::insertAssoc($this->connection, $this->meta->table, $data);
            $method = "set" . StringUtils::firstCharToUpper($this->meta->autoincrement);
            $entity->$method($this->connection->lastInsertId());
        } else {
            DatabaseUtils::insertAssoc($this->connection, $this->meta->table, $data, DatabaseUtils::DUPLICATE_UPDATE);
        }
    }

    private function mapValue(string $key, $value)
    {
        if (is_string($value) || is_numeric($value) || is_null($value)) return $value;
        if (is_bool($value)) return $value ? '1' : '0';
        if (is_object($value)) {

            if (is_a($value, '\mheinzerling\entity\EntityProxy')) {
                $prop = new \ReflectionProperty('\mheinzerling\entity\EntityProxy', 'pk');
                $prop->setAccessible(true);
                $pk = $prop->getValue($value);
                if (count($pk) != 1) throw new \Exception("Can't map foreign key to composed primary keys :" . implode(',', $pk)); //TODO
                return reset($pk);
            } else if (is_subclass_of($value, '\mheinzerling\entity\Entity')) {
                $repoClass = get_class($value) . "Repository";
                $meta = new EntityMetaData(new $repoClass());
                $pk = $meta->pk;
                if (count($pk) != 1) throw new \Exception("Can't map foreign key to composed primary keys :" . implode(',', $pk)); //TODO
                $method = "get" . StringUtils::firstCharToUpper($pk[0]);
                return $value->$method();
            } elseif (is_subclass_of($value, '\Eloquent\Enumeration\AbstractEnumeration')) {
                return $value->value();
            } elseif ($value instanceof \DateTime) {
                return $value->format("Y-m-d H:i:s");
            } else {
                throw new \Exception("Missing database mapping for key " . $key . " with type :" . get_class($value)); //TODO
            }

        }
        throw new \Exception("Missing database mapping for key " . $key . " with type :" . gettype($value)); //TODO

    }

    public abstract function getMeta();

    private function prepareStatement(string $constraint = null, array $values = null):\PDOStatement
    {
        if ($constraint == null) {
            $constraint = '';
        } else {
            $constraint = ' ' . $constraint;
        }
        $stmt = $this->connection->prepare('SELECT * FROM `' . $this->meta->table . '`' . $constraint);
        if ($values != null) {
            foreach ($values as $parameter => $value) {
                $stmt->bindValue(":" . $parameter, $this->mapValue($parameter, $value));
            }
        }
        $stmt->execute();
        return $stmt;
    }

    /**
     * @param null|string $constraint
     * @param array|null $values
     * @return bool|Entity[]
     */
    public function fetchAll(string $constraint = null, array $values = null)
    {
        $stmt = $this->prepareStatement($constraint, $values);
        return $stmt->fetchAll(\PDO::FETCH_CLASS, $this->meta->entityClass);
    }

    /**
     * @param string|null $constraint
     * @param array|null $values
     * @return Entity|bool
     */
    public function fetchUnique(string $constraint = null, array $values = null)
    {
        $stmt = $this->prepareStatement($constraint, $values);
        $stmt->setFetchMode(\PDO::FETCH_CLASS, $this->meta->entityClass);
        return $stmt->fetch();
    }

    /**
     * @param string|array $pk
     * @return bool|Entity
     * @throws \Exception
     */
    public function fetchByPk($pk)
    {
        if (is_array($pk) || count($this->meta->pk) != 1) throw new \Exception("Unsupported operation");
        $key = $this->meta->pk[0];
        return $this->fetchUnique("WHERE `$key`=:$key", [$key => $pk]);
    }

    /**
     * @return \PDO
     */
    protected function getConnection()
    {
        return $this->connection;
    }
}