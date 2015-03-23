<?php

namespace BehatFixturesLoader\Context;

use Behat\Gherkin\Node\TableNode;
use Behat\Symfony2Extension\Context\KernelAwareContext;
use BehatFixturesLoader\EntityFactory\EntityFactory;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Symfony\Component\HttpKernel\KernelInterface;

class FixturesContext implements KernelAwareContext, EntityFactoryAware
{
    private $entityFactory;
    private $container;

    /**
     * @BeforeScenario
     */
    public function purgeDatabase()
    {
        $purger = new ORMPurger($this->getEntityManager());
        $purger->setPurgeMode(ORMPurger::PURGE_MODE_DELETE);
        $purger->purge();
    }

    /**
     * @Given /^there are following :name:$/
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
}
