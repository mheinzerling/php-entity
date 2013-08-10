<?php

namespace mheinzerling\entity;


use mheinzerling\commons\FileUtils;
use mheinzerling\commons\JsonUtils;
use mheinzerling\commons\StringUtils;

class ClassGenerator
{
    public static function loadFromFile($file)
    {
        return new ClassGenerator(JsonUtils::parseToArray(file_get_contents($file)));
    }

    private $config;
    private $src;
    private $gensrc;

    public function __construct(array $jsonConfig)
    {
        $this->config = $jsonConfig;
        $this->src = isset($this->config['src']) ? $this->config['src'] : "src";
        $this->gensrc = isset($this->config['gensrc']) ? $this->config['gensrc'] : "gensrc";
    }

    public function getEntities()
    {
        $entities = array();
        foreach ($this->config['entities'] as $name => $properties) {
            $name = StringUtils::firstCharToUpper($name);
            $ns = isset($properties['namespace']) ? $properties['namespace'] : "";


            $pk = array();
            foreach ($properties as $field => $property) {
                if (isset($property['primary']) && $property['primary']) $pk[] = $field;
            }
            if (count($pk) == 1) $foreignKeys = $pk[0];
            else $foreignKeys = null;
            $entities[$name] = array('namespace' => $ns, 'foreignKey' => $foreignKeys);


        }
        return $entities;
    }

    public function generateFiles()
    {
        $files = array();
        $entities = $this->getEntities();
        $foreignKeys = array();
        foreach ($entities as $name => $e) {
            if ($e['foreignKey'] != null) $foreignKeys["\\" . $e['namespace'] . "\\" . $name] = $e['foreignKey'];
        }
        foreach ($this->config['entities'] as $name => $properties) {
            $name = StringUtils::firstCharToUpper($name);
            $ns = isset($properties['namespace']) ? $properties['namespace'] : "";
            $src = FileUtils::to(FileUtils::append($this->src, $ns), FileUtils::UNIX);
            $gensrc = FileUtils::to(FileUtils::append($this->gensrc, $ns), FileUtils::UNIX);


            foreach ($properties as $field => &$property) {
                if (isset ($property['type'])) {
                    if (isset($entities[$property['type']])) {
                        $property['type'] = "\\" . $entities[$property['type']]['namespace'] . "\\" . $property['type'];
                    }
                }
            }
            $files[FileUtils::append($src, $name . ".php")] = array("content" => PhpSnippets::entity($name, $ns), 'overwrite' => false);
            $files[FileUtils::append($src, $name . "Repository.php")] = array("content" => PhpSnippets::repository($name, $ns), 'overwrite' => false);
            $this->validate($properties, $entities);
            $files[FileUtils::append($gensrc, "Base" . $name . "Repository.php")] = array("content" => PhpSnippets::baserepository($name, $properties), 'overwrite' => true);
            $files[FileUtils::append($gensrc, "Base" . $name . ".php")] = array("content" => PhpSnippets::base($name, $properties, $foreignKeys), 'overwrite' => true);

        }
        if (!isset($this->config['initializer'])) die("Please add a initializer path to the entities.json");
        $namespace = $this->config['initializer'];  //TODO
        $gensrc = FileUtils::to(FileUtils::append($this->gensrc, $namespace), FileUtils::UNIX);
        $files[FileUtils::append($gensrc, "SchemaInitializer.php")] = array("content" => PhpSnippets::initializer($namespace, $entities), 'overwrite' => true);
        return $files;
    }

    private function validate(&$properties, $entities)
    {
        //TODO
//else throw new AnnotationException("Multiple autoincrement values in " . $this->entityClass);
        return array();
    }
}