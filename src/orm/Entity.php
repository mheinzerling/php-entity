<?php
declare(strict_types = 1);

namespace mheinzerling\entity\orm;

class Entity
{
    public function this():Entity
    {
        return $this;
    }
}