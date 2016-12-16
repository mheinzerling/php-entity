<?php
declare(strict_types = 1);

namespace mheinzerling\entity\config;


use mheinzerling\commons\database\structure\builder\DatabaseBuilder;
use mheinzerling\commons\JsonUtils;

class Entity
{
    /**
     * @var string
     */
    private $namespace;
    /**
     * @var Property[]
     */
    private $properties;

    /**
     * @var array[][]
     */
    private $unique;
    /**
     * @var string
     */
    private $name;

    public function __construct(string $name, array $json)
    {
        $this->name = $name;
        $this->namespace = JsonUtils::required($json, 'namespace');
        $this->unique = JsonUtils::optional($json, 'unique', []);

        foreach ($json as $name => $property) {
            if ($name == "namespace") continue;
            if ($name == "unique") continue;
            $this->properties[] = new Property($name, $property);
        }
    }

    /**
     * @param Entity[] $entities
     * @param Enum[] $enums
     */
    public function resolveLazyTypes(array $entities, array $enums)
    {
        foreach ($this->properties as $property) {
            $property->resolveLazyTypes($entities, $enums);
        }
    }

    public function addTo(DatabaseBuilder $databaseBuilder)
    {
        $tableBuilder = $databaseBuilder->table($this->name);
        foreach ($this->unique as $propertyNames) {
            $tableBuilder->unique($propertyNames);
        }
        foreach ($this->properties as $property) {
            $property->addTo($tableBuilder);
        }
        $tableBuilder->complete();
    }

    public function getPrimaryKeyDatabaseTypes()
    {
        $types = [];
        foreach ($this->properties as $property) {
            if ($property->isPrimary()) {
                $types[$property->getName()] = $property->getType()->toDatabaseType(null);
            }
        }
        return $types;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }


}