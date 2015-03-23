<?php

namespace BehatFixturesLoader\Context;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\Initializer\ContextInitializer;
use BehatFixturesLoader\EntityFactory\EntityFactory;

class EntityFactoryAwareContextInitializer implements ContextInitializer
{
    const SERVICE_ID = 'behat_fixtures_loader.context_initializer.entity_factory_aware';

    private $entityFactory;

    public function __construct(EntityFactory $entityFactory)
    {
        $this->entityFactory = $entityFactory;
    }

    /**
     * Initializes provided context.
     *
     * @param Context $context
     */
    public function initializeContext(Context $context)
    {
        if ($context instanceof EntityFactoryAware) {
            $context->setEntityFactory($this->entityFactory);
        }
    }
}