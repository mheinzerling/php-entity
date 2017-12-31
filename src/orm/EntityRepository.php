<?php
declare(strict_types = 1);

namespace mheinzerling\entity\orm;


use Eloquent\Enumeration\AbstractEnumeration;
use mheinzerling\commons\database\ConnectionProvider;
use mheinzerling\commons\database\DatabaseUtils;
use mheinzerling\commons\database\structure\Database;
use mheinzerling\commons\database\structure\Field;
use mheinzerling\meta\language\ANamespace;

abstract class EntityRepository
{
    /**
     * @var \PDO
     */
    private $connection;

    public function __construct(\PDO $connection = null, ANamespace $namespace, string $name, Database $database)
    {
        $this->connection = $connection == null ? ConnectionProvider::get() : $connection;
        $this->database = $database;
        $this->namespace = $namespace;

        $this->repositoryBaseClass = $namespace->resolve("Base" . $name . "Repository");
        $this->repositoryClass = $namespace->resolve($name . "Repository");
        $this->entityBaseClass = $namespace->resolve("Base" . $name);
        $this->entityClass = $namespace->resolve($name);
        $this->table = strtolower($name);
    }

    /**
     * @param Entity $entity
     * @return void
     */
    public function persist(Entity $entity)
    {
        $data = [];
        $table = $this->database->getTables()[$this->table];
        foreach ($table->getFields() as $fieldName => $field) {
            $data[$fieldName] = $this->mapValue($fieldName, $this->get($entity, $field));
            if ($data[$fieldName] == null) {
                if ((!$field->optional()) && $field->default() != null) {
                    unset($data[$fieldName]);
                    $this->set($entity, $field, $field->default());
                }
            }
        }
        $auto = $table->getAutoIncrement();
        if ($auto != null && $data[$auto->getName()] == null) {
            DatabaseUtils::insertAssoc($this->connection, $this->table, $data);
            $this->set($entity, $auto, intval($this->connection->lastInsertId()));

        } else {
            DatabaseUtils::insertAssoc($this->connection, $this->table, $data, DatabaseUtils::DUPLICATE_UPDATE);
        }
    }

    private function mapValue(string $fieldName, $value)
    {
        if (is_string($value) || is_numeric($value) || is_null($value)) return $value;
        if (is_bool($value)) return $value ? '1' : '0';
        if (is_object($value)) {
            if (is_subclass_of($value, Entity::class)) {
                /**
                 * @var $value Entity
                 * @var $repo EntityRepository
                 */
                $repo = (new \ReflectionClass(get_class($value) . "Repository"))->newInstance();
                $pks = $repo->getPrimaryKeyFields();

                if (count($pks) != 1) throw new \Exception("Can't map foreign key to composed primary keys :" . implode(',', $pks)); //TODO
                return $repo->get($value, reset($pks));
            } else if (is_subclass_of($value, AbstractEnumeration::class)) {
                /**
                 * @var $value AbstractEnumeration
                 */
                return $value->key();
            } else if ($value instanceof \DateTime) {
                return $value->format("Y-m-d H:i:s");
            } else {
                throw new \Exception("Missing database mapping for key " . $fieldName . " with type :" . get_class($value)); //TODO
            }

        }
        throw new \Exception("Missing database mapping for key " . $fieldName . " with type :" . gettype($value)); //TODO

    }

    private function prepareStatement(string $constraint = null, array $values = null): \PDOStatement
    {
        if ($constraint == null) {
            $constraint = '';
        } else {
            $constraint = ' ' . $constraint;
        }
        $stmt = $this->connection->prepare('SELECT * FROM `' . $this->table . '`' . $constraint);
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
     * @return Entity[]
     */
    public function fetchAll(string $constraint = null, array $values = null)
    {
        $stmt = $this->prepareStatement($constraint, $values);
        $all = $stmt->fetchAll(\PDO::FETCH_CLASS, $this->entityClass->fullyQualified());
        if ($all === false) return [];
        return $all;
    }

    /**
     * @param string|null $constraint
     * @param array|null $values
     * @return Entity|null
     */
    public function fetchUnique(string $constraint = null, array $values = null): ?Entity
    {
        $stmt = $this->prepareStatement($constraint, $values);
        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $stmt->setFetchMode(\PDO::FETCH_CLASS, $this->entityClass->fullyQualified());
        $result = $stmt->fetch();
        if ($result === false) return null;
        return $result;
    }

    /**
     * @param array $pk
     * @return bool|Entity
     * @throws \Exception
     */
    public function fetchByPk(array $pk)
    {
        $primary = $this->database->getTables()[$this->table]->getPrimary();
        if ($primary == null || count($primary->getFields()) != 1) throw new \Exception("Unsupported operation");
        if (count($pk) != 1) throw new \Exception("Unsupported operation");
        $key = array_keys($primary->getFields())[0];
        return $this->fetchUnique("WHERE `$key`=:$key", [$key => array_values($pk)[0]]);
    }

    /**
     * @return \PDO
     */
    protected function getConnection()
    {
        return $this->connection;
    }

    /**
     * @param Entity $entity
     * @param Field $field
     * @param mixed $value
     */
    protected function set(Entity $entity, Field $field, $value): void
    {
        $prop = new \ReflectionProperty($this->entityBaseClass->fullyQualified(), $field->getName());
        $prop->setAccessible(true);
        $prop->setValue($entity, $value);
    }

    /**
     * @param Entity $entity
     * @param Field $field
     * @return mixed
     */
    protected function get(Entity $entity, Field $field)
    {

        $prop = new \ReflectionProperty($this->entityBaseClass->fullyQualified(), $field->getName());
        $prop->setAccessible(true);
        return $prop->getValue($entity);
    }

    /**
     * @return Field[]
     */
    protected function getPrimaryKeyFields(): ?array
    {
        $primary = $this->database->getTables()[$this->table]->getPrimary();
        if ($primary == null) return [];
        return $primary->getFields();
    }

}