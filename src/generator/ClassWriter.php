<?php
declare(strict_types = 1);
namespace mheinzerling\entity\generator;


class ClassWriter
{
    /**
     * @var string
     */
    private $name;
    /**
     * @var AClass
     */
    private $extends;
    /**
     * @var ANamespace
     */
    private $namespace;
    /**
     * @var FieldWriter[]
     */
    private $fields = [];
    /**
     * @var MethodWriter[]
     */
    private $methods = [];
    /**
     * @var string
     */
    private $doc;
    /**
     * @var mixed[]
     */
    private $constants = [];
    /**
     * @var AClass[]
     */
    private $use = [];
    /**
     * @var bool
     */
    private $final = false;
    /**
     * @var bool
     */
    private $abstract = false;


    public function __construct(string $simpleName)
    {
        $this->name = $simpleName;
        $this->namespace = new ANamespace("\\");
    }

    public function extends (AClass $class): ClassWriter
    {
        $this->extends = $class;
        return $this;
    }

    public function namespace(ANamespace $namespace): ClassWriter
    {
        $this->namespace = $namespace;
        return $this;
    }


    private function line($line)
    {
        return $line . "\n";
    }

    public function write(): string
    {
        $result = $this->line("<?php");
        $result .= $this->line("declare(strict_types = 1);");
        $result .= $this->line("");
        if (!$this->namespace->isRoot()) {
            $result .= $this->line("namespace " . $this->namespace->qualified() . ";");
            $result .= $this->line("");
        }
        $result .= "__USE__";
        if (!empty($this->doc)) {
            $result .= $this->line("/**");
            $result .= $this->doc;
            $result .= $this->line(" */");
        }
        $def = "";
        if ($this->abstract) $def .= "abstract ";
        if ($this->final) $def .= "final ";
        $def .= "class " . $this->name;
        if (!empty($this->extends))
            $def .= " extends " . $this->extends->write($this);
        $result .= $this->line($def);
        $result .= $this->line("{");
        foreach ($this->constants as $name => $value) {
            $result .= $this->line("    const $name = '$value';");
        }

        foreach ($this->fields as $fw) {
            $result .= $fw->create();
            $result .= $this->line("");
        }
        //TODO fields
        foreach ($this->methods as $mw) {
            $result .= $mw->create();
            $result .= $this->line("");
        }
        $result = trim($result);
        $result .= $this->line("");
        $result .= $this->line("}");

        foreach ($this->use as $u) {
            $result = str_replace("__USE__", $this->line("use " . $u->import() . ";") . "__USE__", $result);
        }
        if (count($this->use) > 0) $result = str_replace("__USE__", $this->line("") . "__USE__", $result);
        $result = str_replace("__USE__", "", $result);

        return trim($result);

    }

    public function method(string $name): MethodWriter
    {
        $methodWriter = new MethodWriter($this, $name);
        $this->methods[$name] = $methodWriter;
        return $methodWriter;
    }

    public function field(string $name): FieldWriter
    {
        $fieldWriter = new FieldWriter($this, $name);
        $this->fields[$name] = $fieldWriter;
        return $fieldWriter;
    }

    public function doc(string $line): ClassWriter
    {
        $this->doc .= $this->line(" * " . $line);
        return $this;
    }

    public function const(string $name, $value): ClassWriter
    {
        $this->constants[$name] = $value;
        return $this;
    }

    public function final(bool $final = true): ClassWriter
    {
        $this->final = $final;
        return $this;
    }

    public function use (AClass $class): ClassWriter
    {
        if ($class->getNamespace() == $this->namespace) return $this;

        $this->use[$class->import()] = $class;

        uksort($this->use, function ($a, $b) {
            $partsA = explode(ANamespace::DELIMITER, $a);
            $partsB = explode(ANamespace::DELIMITER, $b);
            for ($c = 0, $s = max(count($partsA), count($partsB)); $c < $s; $c++) {
                if (!isset($partsA[$c])) return -1;
                if (!isset($partsB[$c])) return 1;
                if ($partsA[$c] != $partsB[$c]) return $partsA[$c]<=>$partsB[$c];
            }
            return 0;
        });
        return $this;
    }

    public function abstract ($abstract = true): ClassWriter
    {
        $this->abstract = $abstract;
        return $this;
    }

    public function getNamespace(): ANamespace
    {
        return $this->namespace;
    }
}