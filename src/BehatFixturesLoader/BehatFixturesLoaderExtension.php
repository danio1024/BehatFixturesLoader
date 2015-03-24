<?php

namespace BehatFixturesLoader;

use Behat\Behat\Context\ServiceContainer\ContextExtension;
use Behat\Symfony2Extension\ServiceContainer\Symfony2Extension;
use Behat\Testwork\ServiceContainer\Extension;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use BehatFixturesLoader\Context\EntityFactoryAwareContextInitializer;
use BehatFixturesLoader\EntityFactory\EntityCache;
use BehatFixturesLoader\EntityFactory\EntityFactory;
use BehatFixturesLoader\EntityFactory\EntityMetadataProvider;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class BehatFixturesLoaderExtension implements Extension
{
    /**
     * You can modify the container here before it is dumped to PHP code.
     *
     * @param ContainerBuilder $container
     *
     * @api
     */
    public function process(ContainerBuilder $container)
    {
    }

    /**
     * Returns the extension config key.
     *
     * @return string
     */
    public function getConfigKey()
    {
        return 'behat_fixtures_loader';
    }

    /**
     * Initializes other extensions.
     *
     * This method is called immediately after all extensions are activated but
     * before any extension `configure()` method is called. This allows extensions
     * to hook into the configuration of other extensions providing such an
     * extension point.
     *
     * @param ExtensionManager $extensionManager
     */
    public function initialize(ExtensionManager $extensionManager)
    {
    }

    /**
     * Setups configuration for the extension.
     *
     * @param ArrayNodeDefinition $builder
     */
    public function configure(ArrayNodeDefinition $builder)
    {
        $builder
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('configPath')->defaultValue('features/entities.yml')->end()
                ->booleanNode('useBrutalPurge')->defaultFalse()->end()
            ->end()
        ->end();
    }

    /**
     * Loads extension services into temporary container.
     *
     * @param ContainerBuilder $container
     * @param array $config
     */
    public function load(ContainerBuilder $container, array $config)
    {
        $basePath = $container->getParameter('paths.base');
        $container->setParameter('behat_fixtures_loader.config_path', $basePath.'/'.$config['configPath']);
        $container->setParameter('behat_fixtures_loader.use_brutal_purge', (bool) $config['useBrutalPurge']);

        $this->loadEntityCache($container);
        $this->loadMetadataProvider($container);
        $this->loadEntityFactory($container);
        $this->loadEntityFactoryContextInitializer($container);
    }

    /**
     * @param ContainerBuilder $container
     */
    public function loadEntityCache(ContainerBuilder $container)
    {
        $definition = new Definition(EntityCache::class);
        $container->setDefinition(EntityCache::SERVICE_ID, $definition);
    }

    /**
     * @param ContainerBuilder $container
     */
    public function loadMetadataProvider(ContainerBuilder $container)
    {
        $definition = new Definition(EntityMetadataProvider::class, [
            new Reference(Symfony2Extension::KERNEL_ID),
            new Reference(EntityCache::SERVICE_ID),
            '%behat_fixtures_loader.config_path%'

        ]);
        $container->setDefinition(EntityMetadataProvider::SERVICE_ID, $definition);
    }

    /**
     * @param ContainerBuilder $container
     */
    public function loadEntityFactory(ContainerBuilder $container)
    {
        $definition = new Definition(EntityFactory::class, [
            new Reference(Symfony2Extension::KERNEL_ID),
            new Reference(EntityMetadataProvider::SERVICE_ID),
            new Reference(EntityCache::SERVICE_ID),

        ]);
        $container->setDefinition(EntityFactory::SERVICE_ID, $definition);
    }

    private function loadEntityFactoryContextInitializer(ContainerBuilder $container)
    {
        $definition = new Definition(EntityFactoryAwareContextInitializer::class, array(
            new Reference(EntityFactory::SERVICE_ID),
            '%behat_fixtures_loader.use_brutal_purge%'
        ));
        $definition->addTag(ContextExtension::INITIALIZER_TAG, array('priority' => 1));
        $container->setDefinition(EntityFactoryAwareContextInitializer::SERVICE_ID, $definition);
    }
}