<?php
declare(strict_types = 1);

namespace mheinzerling\entity\orm;

class Entity
{
    protected function __construct()
    {

    }

    public function this(): Entity
    {
        return $this;
    }
}