<?php

namespace BehatFixturesLoader\Context;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\Initializer\ContextInitializer;
use BehatFixturesLoader\EntityFactory\EntityFactory;

class EntityFactoryAwareContextInitializer implements ContextInitializer
{
    const SERVICE_ID = 'behat_fixtures_loader.context_initializer.entity_factory_aware';

    /**
     * @var EntityFactory
     */
    private $entityFactory;

    /**
     * @var bool
     */
    private $useBrutalPurge;

    public function __construct(EntityFactory $entityFactory, $useBrutalPurge)
    {
        $this->entityFactory = $entityFactory;
        $this->useBrutalPurge = $useBrutalPurge;
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
            $context->setBrutalPurge($this->useBrutalPurge);
        }
    }
}