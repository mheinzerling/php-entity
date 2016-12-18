<?php
declare(strict_types = 1);

namespace mheinzerling\entity\generator;


class MethodWriter
{
    const NODEFAULT = "__NODEFAULT__";
    /**
     * @var string
     */
    private $name;
    /**
     * @var ClassWriter
     */
    private $classWriter;
    /**
     * @var string
     */
    private $visibility = "private";
    /**
     * @var PHPType
     */
    private $return = null;
    /**
     * @var string
     */
    private $body;
    /**
     * @var array[]
     */
    private $params = [];
    /**
     * @var bool
     */
    private $static = false;

    public function __construct(ClassWriter $classWriter, string $name)
    {
        $this->name = $name;
        $this->classWriter = $classWriter;
    }

    public function create(): string
    {
        $result = '    ' . $this->visibility;
        if ($this->static) $result .= ' static';
        $result .= ' function ' . $this->name . '(';
        foreach ($this->params as $name => $p) {
            $type = $p['type'];
            $default = $p['default'];
            $result .= $type->toPHP($this->classWriter) . ' $' . $name;
            if ($default != self::NODEFAULT) {
                $result .= ' = ';
                if ($default == null) $result .= "null";
                elseif (is_numeric($default)) $result .= $default;
                else $result .= "'" . $default . "'"; //TODO special cases
            }
            $result .= ', ';
        }
        if (count($this->params) > 0) $result = substr($result, 0, -2);
        $result .= ')';
        if ($this->return != null) $result .= ': ' . $this->return->toPHP($this->classWriter);
        $result .= "\n";
        $result .= "    {\n";
        $result .= $this->body;
        $result .= "    }\n";
        return $result;
    }

    public function public (): MethodWriter
    {
        $this->visibility = "public";
        return $this;
    }

    public function private (): MethodWriter
    {
        $this->visibility = "private";
        return $this;
    }

    public function protected (): MethodWriter
    {
        $this->visibility = "protected";
        return $this;
    }

    public function void(): MethodWriter
    {
        return $this->return(new PrimitivePHPType(Primitive::VOID()));
    }

    public function return (PHPType $type): MethodWriter
    {
        $this->return = $type;
        return $this;
    }

    public function write(): string
    {
        return $this->classWriter->write();
    }

    public function line(string $line): MethodWriter
    {
        $this->body .= '        ' . $line . "\n";
        return $this;
    }

    public function use (AClass $class): MethodWriter
    {
        $this->classWriter->use($class);
        return $this;
    }

    public function param($name, PHPType $type, $default = self::NODEFAULT): MethodWriter
    {
        $this->params[$name] = ['type' => $type, 'default' => $default];
        return $this;
    }

    public function getClassWriter(): ClassWriter
    {
        return $this->classWriter;
    }

    public function static (bool $static = true): MethodWriter
    {
        $this->static = $static;
        return $this;
    }


}