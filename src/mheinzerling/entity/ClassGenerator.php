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

    public function getEntitiesRelations()
    {
        $entities = array();
        foreach ($this->getEntities() as $name => $properties) {
            $name = StringUtils::firstCharToUpper($name);
            if (isset($properties['namespace']) && !StringUtils::isBlank($properties['namespace'])) {
                $ns = $properties['namespace'];
            } else {
                $ns = "";
            }

            $pk = array();
            $fks = array();
            foreach ($properties as $field => $property) {
                if (isset($property['primary']) && $property['primary']) $pk[] = $field;

                if (isset($property['type']) && TypeUtil::isEntityOrEnum($property['type'])) {
                    if (!isset($this->config['entities'][$property['type']])) continue; //enum

                    $table = StringUtils::firstCharToLower($property['type']);
                    $fks[$field] = array(
                        'table' => $table,
                        'fields' => $entities[$property['type']]['pk'],
                        'update' => 'CASCADE', 'delete' => 'RESTRICT' //TODO
                    );
                }
            }
            $entities[$name] = array('namespace' => $ns, 'pk' => $pk, 'fks' => $fks);


        }
        return $entities;
    }

    public function getEntities()
    {
        $sorted = array();
        foreach ($this->config['entities'] as $name => $entity) {
            if (isset($sorted[$name])) continue;
            $this->addSelfAndDependent($this->config['entities'], $sorted, $name, $entity);

        }
        return $sorted;
    }

    private function addSelfAndDependent($entities, &$sorted, $name, $entity)
    {
        foreach ($entity as $field => $property) {
            if (isset($property['type']) && TypeUtil::isEntityOrEnum($property['type'])) {
                if (!isset($entities[$property['type']])) continue; //enum
                $this->addSelfAndDependent($entities, $sorted, $property['type'], $entities[$property['type']]);
            }
        }
        $sorted[$name] = $entity;
    }

    public function getEnums()
    {
        return $this->config['enums'];
    }

    public function generateFiles()
    {
        $files = array();
        $entitiesRelations = $this->getEntitiesRelations();
        $enums = $this->getEnums();

        foreach ($this->getEntities() as $name => $properties) {
            $name = StringUtils::firstCharToUpper($name);
            $ns = isset($properties['namespace']) ? $properties['namespace'] : "";
            $src = FileUtils::to(FileUtils::append($this->src, $ns), FileUtils::UNIX);
            $gensrc = FileUtils::to(FileUtils::append($this->gensrc, $ns), FileUtils::UNIX);


            foreach ($properties as $field => &$property) {
                if (isset ($property['type'])) {
                    if (isset($entitiesRelations[$property['type']])) {
                        $property['type'] = "\\" . $entitiesRelations[$property['type']]['namespace'] . "\\" . $property['type'];
                    }
                    if (isset($enums[$property['type']])) {
                        $property['values'] = $enums[$property['type']]['values'];
                        $property['type'] = "\\" . $enums[$property['type']]['namespace'] . "\\" . $property['type'];
                    }
                }
            }
            $files[FileUtils::append($src, $name . ".php")] = array("content" => PhpSnippets::entity($name, $ns), 'overwrite' => false);
            $files[FileUtils::append($src, $name . "Repository.php")] = array("content" => PhpSnippets::repository($name, $ns), 'overwrite' => false);
            $this->validate($properties, $entitiesRelations);
            $files[FileUtils::append($gensrc, "Base" . $name . "Repository.php")] = array("content" => PhpSnippets::baserepository($name, $properties, $entitiesRelations[$name]['fks']), 'overwrite' => true);
            $files[FileUtils::append($gensrc, "Base" . $name . ".php")] = array("content" => PhpSnippets::base($name, $properties, $entitiesRelations, $enums), 'overwrite' => true);

        }
        foreach ($enums as $enum => $property) {

            $src = FileUtils::to(FileUtils::append($this->src, $property['namespace']), FileUtils::UNIX);
            $files[FileUtils::append($src, $enum . ".php")] = array("content" => PhpSnippets::enum($property['namespace'], $enum, $property['values']), 'overwrite' => false);
        }

        if (!isset($this->config['initializer'])) die("Please add a initializer path to the entities.json");
        $namespace = $this->config['initializer']; //TODO
        $gensrc = FileUtils::to(FileUtils::append($this->gensrc, $namespace), FileUtils::UNIX);
        $files[FileUtils::append($gensrc, "SchemaInitializer.php")] = array("content" => PhpSnippets::initializer($namespace, $entitiesRelations), 'overwrite' => true);
        return $files;
    }

    private function validate(&$properties, $entities)
    {
        //TODO
//else throw new AnnotationException("Multiple autoincrement values in " . $this->entityClass);
        return array();
    }
}