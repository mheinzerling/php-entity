<?php
declare(strict_types = 1);

namespace mheinzerling\entity\generator;


class FieldWriter
{
    /**
     * @var ClassWriter
     */
    private $classWriter;
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $visibility = "private";
    /**
     * @var PHPType
     */
    private $type = "mixed";
    /**
     * @var bool
     */
    private $static;

    public function __construct(ClassWriter $classWriter, string $name)
    {
        $this->classWriter = $classWriter;
        $this->name = $name;
    }

    public function create(): string
    {
        $result = "    /**\n";
        $result .= "     * @var " . $this->type->toPHPDoc($this->classWriter) . "\n";
        $result .= "     */\n";
        $result .= "    " . $this->visibility;
        if ($this->static) $result .= " static";
        $result .= " $" . $this->name . ";\n";
        return $result;
    }

    public function public (): FieldWriter
    {
        $this->visibility = "public";
        return $this;
    }

    public function private (): FieldWriter
    {
        $this->visibility = "private";
        return $this;
    }

    public function protected (): FieldWriter
    {
        $this->visibility = "protected";
        return $this;
    }

    public function static (bool $static = true): FieldWriter
    {
        $this->static = $static;
        return $this;
    }

    public function type(PHPType $type): FieldWriter
    {
        $this->type = $type;
        return $this;
    }
}