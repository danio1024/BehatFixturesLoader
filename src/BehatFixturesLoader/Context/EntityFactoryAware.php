<?php

namespace BehatFixturesLoader\Context;

use BehatFixturesLoader\EntityFactory\EntityFactory;

interface EntityFactoryAware
{
    public function setEntityFactory(EntityFactory $entityFactory);
    public function setBrutalPurge($useBrutalPurge);
}