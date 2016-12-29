<?php
declare(strict_types = 1);
namespace mheinzerling\entity\config;


use mheinzerling\meta\language\PHPType;
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
        if (isset($entities[$this->type])) {
            $type = new EntityPHPType($entities[$this->type]);
            $type->setOptional($this->isOptional());
            return $type;
        }
        if (isset($enums[$this->type])) {
            $type = new EnumPHPType($enums[$this->type]);
            $type->setOptional($this->isOptional());
            return $type;
        }
        throw new ParsingException(">" . $this->type . "< is neither an entity nor an enum. If it is an other class use the full qualified name.");
    }

    public function toOptional(): PHPType
    {
        throw new \Exception("Unsupported operation");
    }
}