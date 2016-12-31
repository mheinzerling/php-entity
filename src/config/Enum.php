<?php
declare(strict_types = 1);

namespace mheinzerling\entity\config;


use Eloquent\Enumeration\AbstractEnumeration;
use mheinzerling\commons\FileUtils;
use mheinzerling\commons\JsonUtils;
use mheinzerling\commons\Separator;
use mheinzerling\meta\language\AClass;
use mheinzerling\meta\language\ANamespace;
use mheinzerling\meta\writer\ClassWriter;

class Enum
{
    /**
     * @var ANamespace
     */
    private $namespace;

    /**
     * @var array[]
     */
    private $values;
    /**
     * @var string
     */
    private $name;

    public function __construct(string $name, array $json)
    {
        $this->name = $name;
        JsonUtils::validProperties($json, ["namespace", "values"]);
        $this->namespace = ANamespace::absolute(JsonUtils::required($json, 'namespace'));
        foreach (JsonUtils::required($json, 'values') as $k => $v) $this->values[$k] = $v;
    }

    /**
     * @return \array[]
     */
    public function getValues(): array
    {
        return $this->values;
    }

    public function toPHPFile(): string
    {
        $writer = (new ClassWriter($this->name))->extends(AClass::absolute(AbstractEnumeration::class))
            ->namespace($this->namespace)->final();
        foreach ($this->values as $key => $value) {
            $key = strtoupper($key);
            $value = strtoupper($value);
            $writer->doc("@method static $this->name $value()");
            $writer->const($value, $key);
        }
        return $writer->write();
    }

    public function getFullyQualifiedName(): string
    {
        return $this->namespace->fullyQualified() . ANamespace::DELIMITER . $this->name;
    }

    public function getClass(): AClass
    {
        return $this->namespace->resolve($this->name);
    }

    public function generateFiles(string $src): array
    {
        $src = FileUtils::to(FileUtils::append($src, $this->namespace->fullyQualified()), Separator::UNIX());
        $files[FileUtils::append($src, ucfirst($this->name) . ".php")] = ["content" => $this->toPHPFile(), 'overwrite' => false];
        return $files;
    }

}