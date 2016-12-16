<?php
declare(strict_types = 1);

namespace mheinzerling\entity\config;


use Eloquent\Enumeration\Exception\UndefinedMemberException;
use mheinzerling\commons\database\structure\builder\TableBuilder;
use mheinzerling\commons\database\structure\index\ReferenceOption;
use mheinzerling\commons\JsonUtils;
use mheinzerling\commons\StringUtils;

class Property
{
    /**
     * @var string
     */
    private $name;
    /**
     * @var PHPType
     */
    private $type;
    /**
     * @var int|null
     */
    private $length;
    /**
     * @var bool
     */
    private $primary;
    /**
     * @var bool
     */
    private $optional;
    /**
     * @var bool
     */
    private $autoIncrement;
    /**
     * @var string
     */
    private $default;


    public function __construct(string $name, array $json)
    {
        JsonUtils::validProperties($json, ["type", "length", "primary", "optional", "auto", "default"]);

        $this->name = $name;
        $type = JsonUtils::required($json, 'type');
        try {
            $this->type = new PrimitivePHPType(Primitive::memberByValue($type));
        } catch (UndefinedMemberException $e) {
            if (StringUtils::contains($type, "\\")) $this->type = new ClassPHPType($type);
            else $this->type = new LazyEntityEnumPHPType($type);
        }
        $this->length = JsonUtils::optional($json, 'length', null);
        $this->primary = JsonUtils::optional($json, 'primary', false);
        $this->optional = JsonUtils::optional($json, 'optional', false);
        $this->autoIncrement = JsonUtils::optional($json, 'auto', false);
        $this->default = (string)JsonUtils::optional($json, 'default', null);
    }

    /**
     * @param Entity[] $entities
     * @param Enum[] $enums
     */
    public function resolveLazyTypes($entities, $enums)
    {
        if (!$this->type instanceof LazyEntityEnumPHPType) return;
        $this->type = $this->type->toEntityEnum($entities, $enums);
    }

    public function addTo(TableBuilder $tableBuilder)
    {
        $type = $this->type->toDatabaseType($this->length);//TODO collation
        $fieldBuilder = $tableBuilder->field($this->name);
        $fieldBuilder->type($type);
        $fieldBuilder->primary($this->primary)->null($this->optional)->autoincrement($this->autoIncrement)->default($this->default);
        $fieldBuilder->complete();
        if ($this->type instanceof EntityPHPType) {
            //TODO multi field foreign key
            $entity = $this->type->getEntity();
            $tableBuilder->foreign([$this->name], $entity->getName(), array_keys($entity->getPrimaryKeyDatabaseTypes()), ReferenceOption::CASCADE(), ReferenceOption::RESTRICT()); //TODO
        }
    }

    public function isPrimary(): bool
    {
        return $this->primary;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): PHPType
    {
        return $this->type;
    }
}