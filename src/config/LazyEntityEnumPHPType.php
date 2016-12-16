<?php
declare(strict_types = 1);
namespace mheinzerling\entity\config;


use mheinzerling\commons\database\structure\type\Type;
use Seld\JsonLint\ParsingException;

class LazyEntityEnumPHPType extends PHPType
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
        if (isset($entities[$this->type])) return new EntityPHPType($entities[$this->type]);
        if (isset($enums[$this->type])) return new EnumPHPType($enums[$this->type]);
        throw new ParsingException(">" . $this->type . "< is neither an entity nor an enum. If it is an other class use the full qualified name.");
    }

    public function toDatabaseType(int $length = null): Type
    {
        throw new \Exception("Unsupported operation");
    }
}