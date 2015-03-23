<?php

namespace BehatFixturesLoader\EntityFactory;

use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Yaml\Parser;

class EntityMetadataProvider
{
    const SERVICE_ID = 'behat_fixtures_loader.metadata_provider';

    private $entities;

    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    private $container;

    /**
     * @var EntityCache
     */
    private $entityCache;

    /**
     * Configuration path
     *
     * @var string
     */
    private $configPath;

    public function __construct(Kernel $kernel, EntityCache $entityCache, $configPath)
    {
        $this->container = $kernel->getContainer();
        $this->entityCache = $entityCache;
        $this->configPath = $configPath;

        $this->warmupEntities();
    }

    /**
     * @param $shortName
     * @throws \InvalidArgumentException
     * @return EntityMetadata
     */
    public function getEntityMetadata($shortName)
    {
        if (!isset($this->entities[$shortName])) {
            throw new \InvalidArgumentException(
                'Entity with name "'.$shortName.'" does not exist. Did you forget to add it to your config?'
            );
        }

        return $this->entities[$shortName];
    }

    private function warmupEntities()
    {
        $yamlParser = new Parser();

        $configPath = $this->configPath;

        $config = $yamlParser->parse(file_get_contents($configPath));

        $resolver = new OptionsResolver();
        $resolver->setRequired(['class']);
        $resolver->setDefined(['hook', 'parent']);

        $hookResolver = new OptionsResolver();
        $hookResolver->setRequired(['class']);
        $hookResolver->setDefined(['args']);

        foreach ($config['entities'] as $shortName => $entityConfig) {
            $entityConfig = $resolver->resolve($entityConfig);

            $this->entities[$shortName] = new EntityMetadata($entityConfig['class']);

            if (isset($entityConfig['parent'])) {
                $this->entities[$shortName]->setParentEntity($entityConfig['parent']);
            }

            if (isset($entityConfig['hook'])) {
                $entityConfig['hook'] = $hookResolver->resolve($entityConfig['hook']);

                $hook = $this->createHook($entityConfig['hook']);

                $this->entities[$shortName]->setHook($hook);
            }
        }
    }

    private function createHook($hookOptions)
    {
        $class = $hookOptions['class'];

        $args = [];

        if (isset($hookOptions['args'])) {
            foreach ($hookOptions['args'] as $arg) {
                if ($arg === "entity_cache") {
                    $args[] = $this->entityCache;
                }

                $args[] = $this->container->get($arg);
            }
        }

        return $class::init($args);
    }
}
