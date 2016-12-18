<?php
declare(strict_types = 1);

namespace mheinzerling\entity\config;


use mheinzerling\commons\database\structure\builder\DatabaseBuilder;
use mheinzerling\commons\FileUtils;
use mheinzerling\commons\JsonUtils;
use mheinzerling\commons\StringUtils;
use mheinzerling\entity\generator\AClass;
use mheinzerling\entity\generator\ANamespace;
use mheinzerling\entity\generator\ClassPHPType;
use mheinzerling\entity\generator\ClassWriter;
use mheinzerling\entity\orm\EntityRepository;

class Entity
{
    /**
     * @var ANamespace
     */
    private $namespace;
    /**
     * @var Property[]
     */
    private $properties = [];

    /**
     * @var array[][]
     */
    private $unique;
    /**
     * @var string
     */
    private $name;
    /**
     * @var AClass
     */
    private $modelClass;

    public function __construct(string $name, AClass $modelClass, array $json)
    {
        $this->name = $name;
        $this->namespace = ANamespace::absolute(JsonUtils::required($json, 'namespace', "Missing namespace for entity '" . $name . "'"));
        $this->unique = JsonUtils::optional($json, 'unique', []);
        $this->modelClass = $modelClass;

        foreach ($json as $name => $property) {
            if ($name == "namespace") continue;
            if ($name == "unique") continue;
            $this->properties[$name] = new Property($name, $property);
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
        $tableBuilder = $databaseBuilder->table($this->getTableName());
        foreach ($this->unique as $propertyNames) {
            $tableBuilder->unique($propertyNames);
        }
        foreach ($this->properties as $property) {
            $property->addTo($tableBuilder);
        }
        $tableBuilder->complete();
    }

    public function getTableName(): string
    {
        return strtolower($this->name);
    }

    public function getPrimaryKeyProperties()
    {
        return array_filter($this->properties, function (Property $p) {
            return $p->isPrimary();
        });
    }

    public function getForeignKeyProperties()
    {
        return array_filter($this->properties, function (Property $p) {
            return $p->getType() instanceof EntityPHPType;
        });
    }

    public function getAutoIncrementProperty(): ?Property
    {
        $auto = array_filter($this->properties, function (Property $p) {
            return $p->isAutoIncrement();
        });
        if (count($auto) == 0) return null;
        if (count($auto) > 1) throw new \Exception("Only one autoincrement field allowed");
        return reset($auto);

    }

    public function getPrimaryKeyDatabaseTypes()
    {
        $types = [];
        foreach ($this->getPrimaryKeyProperties() as $property) {
            $types[$property->getName()] = $property->getType()->toDatabaseType(null);
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

    public function toRepositoryPHPFile(): string
    {
        $base = $this->namespace->resolve("Base" . $this->name . "Repository");
        return (new ClassWriter($this->name . "Repository"))->extends($base)->namespace($this->namespace)->write();
    }

    public function toEntityPHPFile(): string
    {
        return (new ClassWriter($this->name))->extends($this->getBaseClass())->namespace($this->namespace)->write();
    }

    public function toEntityBasePHPFile(): string
    {
        $classWriter = (new ClassWriter("Base" . $this->name))->namespace($this->namespace)->abstract()->extends(AClass::of("\\" . \mheinzerling\entity\orm\Entity::class));

        foreach ($this->properties as $field => $property) {
            $classWriter->field($field)->protected()->type($property->getType());
        }
        $methodWriter = $classWriter->method("__construct")->public();
        $methodWriter->line("parent::__construct();");
        foreach ($this->properties as $field => $property) {
            $property->fixInjection($methodWriter);
        }


        foreach ($this->properties as $field => $property) {
            $uField = StringUtils::firstCharToUpper($field);
            //TODO final
            $getOrIs = $property->getType()->getterPrefix();
            if (!$property->isAutoIncrement()) $classWriter->method('set' . $uField)->public()->param($field, $property->getType())->void()->line("\$this->$field = $$field;");

            $type = $property->getType();
            if ($property->isAutoIncrement()) $type = $type->toOptional();
            $classWriter->method($getOrIs . $uField)->public()->return($type)->line("return \$this->$field;");
        }
        return $classWriter->write();
    }

    public function getClass(): AClass
    {
        return $this->namespace->resolve($this->name);
    }

    public function getPHPType(bool $optional = false): EntityPHPType
    {
        $type = new EntityPHPType($this);
        $type->setOptional($optional);
        return $type;
    }

    public function getRepositoryClass(): AClass
    {
        return $this->namespace->resolve($this->name . "Repository");
    }


    public function getBaseClass(): AClass
    {
        return $this->namespace->resolve("Base" . $this->name);
    }

    public function toRepositoryBasePHPFile()
    {
        $classWriter = (new ClassWriter("Base" . $this->name . "Repository"))->namespace($this->namespace)->extends(AClass::of("\\" . EntityRepository::class))->abstract();

        $methodWriter = $classWriter->method("__construct")->public()->param("connection", new ClassPHPType(AClass::of("\\" . \PDO::class)), null);
        $methodWriter->line("parent::__construct(");
        $methodWriter->line("    \$connection,");
        $methodWriter->line("    " . (AClass::of("\\" . ANamespace::class))->write($classWriter) . "::absolute('" . $this->namespace->fullyQualified() . "'),");
        $methodWriter->line("    '$this->name',");
        $methodWriter->line('    ' . $this->modelClass->write($classWriter) . '::getDatabase()');
        $methodWriter->line(");");


        $primaryKeyProperties = $this->getPrimaryKeyProperties();
        $primaryKeyNames = array_keys($primaryKeyProperties);

        if (count($primaryKeyProperties) > 0) {

            $upk = array_map(StringUtils::class . '::firstCharToUpper', $primaryKeyNames);
            $binding = array_map(function ($n) {
                return "`$n`=:$n";
            }, $primaryKeyNames);
            $array = array_map(function ($n) {
                return "'$n'=>\$$n";
            }, $primaryKeyNames);

            $methodWriter = $classWriter->method("fetchBy" . implode("And", $upk))->public()->return($this->getPHPType(true));
            foreach ($primaryKeyProperties as $primary) {
                $methodWriter->param($primary->getName(), $primary->getType());
            }
            $methodWriter->line("/** @noinspection PhpIncompatibleReturnTypeInspection */");
            $methodWriter->line("return \$this->fetchUnique(\"WHERE " . implode(" AND ", $binding) . "\", [" . implode(", ", $array) . "]);");
        }
        return $classWriter->write();
    }

    public function generateFiles(string $src, string $gensrc): array
    {
        $src = FileUtils::to(FileUtils::append($src, $this->namespace->fullyQualified()), FileUtils::UNIX);
        $gensrc = FileUtils::to(FileUtils::append($gensrc, $this->namespace->fullyQualified()), FileUtils::UNIX);
        $files = [];
        $name = ucfirst($this->name);
        $files[FileUtils::append($src, $name . ".php")] = ["content" => $this->toEntityPHPFile(), 'overwrite' => false];
        $files[FileUtils::append($src, $name . "Repository.php")] = ["content" => $this->toRepositoryPHPFile(), 'overwrite' => false];
        $files[FileUtils::append($gensrc, "Base" . $name . ".php")] = ["content" => $this->toEntityBasePHPFile(), 'overwrite' => true];
        $files[FileUtils::append($gensrc, "Base" . $name . "Repository.php")] = ["content" => $this->toRepositoryBasePHPFile(), 'overwrite' => true];
        return $files;
    }


}