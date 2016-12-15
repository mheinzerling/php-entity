<?php

namespace mheinzerling\entity\config;


use mheinzerling\commons\database\structure\builder\DatabaseBuilder;
use mheinzerling\commons\JsonUtils;

class Config
{
    /**
     * @var string
     */
    private $gensrc;
    /**
     * @var string
     */
    private $src;
    /**
     * @var string
     */
    private $initializer;

    /**
     * @var Entity[]
     */
    private $entities = [];

    /**
     * @var Enum[]
     */
    private $enums = [];


    public function __construct(array $json)
    {
        JsonUtils::validProperties($json, ["gensrc", "src", "initializer", "entities", "enums"]);
        $this->gensrc = JsonUtils::required($json, 'gensrc');
        $this->src = JsonUtils::required($json, 'src');
        $this->initializer = JsonUtils::required($json, 'initializer');
        foreach (JsonUtils::optional($json, 'entities', []) as $name => $e) $this->entities[$name] = new Entity($name, $e);
        foreach (JsonUtils::optional($json, 'enums', []) as $name => $e) $this->enums[$name] = new Enum($name, $e);

        foreach ($this->entities as $entity) {
            $entity->resolveLazyTypes($this->entities, $this->enums);
        }
    }

    public static function load(string $file): Config
    {
        $json = JsonUtils::parseToArray(file_get_contents($file));
        return new Config($json);
    }

    public function addTo(DatabaseBuilder $databaseBuilder)
    {
        foreach ($this->entities as $entity) {
            $entity->addTo($databaseBuilder);
        }
    }


}