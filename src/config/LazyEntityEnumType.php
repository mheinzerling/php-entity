<?php
namespace mheinzerling\entity\config;


use Seld\JsonLint\ParsingException;

class LazyEntityEnumType extends Type
{
    /**
     * @var string
     */
    private $type;

    public function __construct(string $type)
    {
        $this->type = $type;
    }

    public function toEntityEnum(array $entities, array $enums)
    {
        if (isset($entities[$this->type])) return new EntityType($entities[$this->type]);
        if (isset($enums[$this->type])) return new EnumType($enums[$this->type]);
        throw new ParsingException(">" . $this->type . "< is neither an entity nor an enum. If it is an other class use the full qualified name.");
    }

    public function toDatabaseType(int $length = null): \mheinzerling\commons\database\structure\type\Type
    {
        throw new \Exception("Unsupported operation");
    }
}