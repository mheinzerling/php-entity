<?php
declare(strict_types = 1);

namespace mheinzerling\entity\generator;


use mheinzerling\commons\FileUtils;

class AClass
{

    /**
     * @var ANamespace
     */
    private $namespace;

    /**
     * @var string
     */
    private $simpleName;

    public static function of(string $qualifiedName): AClass
    {
        $className = new AClass();
        $className->simpleName = FileUtils::basename($qualifiedName);
        $className->namespace = ANamespace::absolute(substr($qualifiedName, 0, -strlen($className->simpleName)));
        return $className;
    }

    public static function resolve(ANamespace $namespace, $simpleClassName)
    {
        $className = new AClass();
        $className->simpleName = $simpleClassName;
        $className->namespace = $namespace;
        return $className;
    }

    public function write(ClassWriter $classWriter): string //TODO relative namespaces
    {
        if ($this->namespace->isRoot()) {
            if ($classWriter->getNamespace()->isRoot()) return $this->simpleName;
            return $this->fullyQualified();
        } else {
        }
        $classWriter->use($this);
        return $this->simpleName;

    }

    public function getNamespace(): ANamespace
    {
        return $this->namespace;
    }

    public function import(): string
    {
        return $this->getNamespace()->qualified() . ANamespace::DELIMITER . $this->simpleName;
    }

    public function fullyQualified(): string
    {
        if ($this->namespace->isRoot())
            return $this->getNamespace()->fullyQualified() . $this->simpleName;
        else
            return $this->getNamespace()->fullyQualified() . ANamespace::DELIMITER . $this->simpleName;
    }

    public function simple(): string
    {
        return $this->simpleName;
    }


}