<?php

namespace BehatFixturesLoader\EntityFactory;

class EntityCache
{
    const SERVICE_ID = 'behat_fixtures_loader.entity_cache';

    /**
     * @var array
     */
    private $objectCache = [];

    public function store($object, $type, $id)
    {
        $this->objectCache[$type][$id] = $object;
    }

    public function fetch($type, $id)
    {
        return $this->objectCache[$type][$id];
    }
}
