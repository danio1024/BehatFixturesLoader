<?php

namespace BehatFixturesLoader\Context;

use Behat\Gherkin\Node\TableNode;
use Behat\Symfony2Extension\Context\KernelAwareContext;
use BehatFixturesLoader\EntityFactory\EntityFactory;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class FixturesContext implements KernelAwareContext, EntityFactoryAware
{
    /**
     * @var EntityFactory
     */
    private $entityFactory;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var bool
     */
    private $useBrutalPurge;

    /**
     * @BeforeScenario
     */
    public function purgeDatabase()
    {
        $purger = new ORMPurger($this->getEntityManager());

        if ($this->useBrutalPurge) {
            $connection = $this->getEntityManager()->getConnection();

            try {
                $connection->executeQuery('SET FOREIGN_KEY_CHECKS = 0');
                $purger->purge();
            } finally {
                $connection->executeQuery('SET FOREIGN_KEY_CHECKS = 1');
            }
        } else {
            $purger->setPurgeMode(ORMPurger::PURGE_MODE_DELETE);
            $purger->purge();
        }
    }

    /**
     * @Given there are following :name:
     * @param $name
     */
    public function followingEntities($name, TableNode $data)
    {
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->getEntityManager();

        if (!$this->entityFactory) {
            throw new \InvalidArgumentException('EntityFactory was not injected to this context');
        }

        foreach ($data->getHash() as $dataRow) {
            $entity = $this->entityFactory->createEntity($name, $dataRow);

            $entityManager->persist($entity);
        }

        $entityManager->flush();
    }

    /**
     * Sets Kernel instance.
     *
     * @param KernelInterface $kernel
     */
    public function setKernel(KernelInterface $kernel)
    {
        $this->container = $kernel->getContainer();
    }

    private function getEntityManager()
    {
        return $this->container->get('doctrine.orm.entity_manager');
    }

    public function setEntityFactory(EntityFactory $entityFactory)
    {
        $this->entityFactory = $entityFactory;
    }

    public function setBrutalPurge($useBrutalPurge)
    {
        $this->useBrutalPurge = $useBrutalPurge;
    }
}
