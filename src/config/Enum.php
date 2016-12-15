<?php

namespace mheinzerling\entity\config;


use mheinzerling\commons\JsonUtils;

class Enum
{
    /**
     * @var string
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
        $this->namespace = JsonUtils::required($json, 'namespace');
        foreach (JsonUtils::required($json, 'values') as $k => $v) $this->values[$k] = $v;
    }

    /**
     * @return \array[]
     */
    public function getValues(): array
    {
        return $this->values;
    }
}