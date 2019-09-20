<?php

namespace BehatFixturesLoader\EntityFactory;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Symfony\Component\HttpKernel\Kernel;

class EntityFactory
{
    const SERVICE_ID = 'behat_fixtures_loader.entity_factory';

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $entityManager;

    /**
     * @var EntityMetadataProvider
     */
    private $entities;

    /**
     * @var EntityCache
     */
    private $entityCache;

    /**
     * @var EntityFieldHandler
     */
    private $entityFieldHandler;

    public function __construct(
        Kernel $kernel,
        EntityMetadataProvider $entityMetadataProvider,
        EntityCache $entityCache
    ) {
        $this->entityManager = $kernel->getContainer()->get('doctrine.orm.entity_manager');
        $this->entityCache = $entityCache;
        $this->entities = $entityMetadataProvider;
        $this->entityFieldHandler = new EntityFieldHandler($this->entityCache, $this->entityManager);
    }

    public function createEntity($entityShortName, $dataFromBehat)
    {
        $entityMetadata = $this->entities->getEntityMetadata($entityShortName);

        $entityFullName = $entityMetadata->getFullName();

        $entity = new $entityFullName();

        $classMetadata = $this->entityManager->getClassMetadata($entityFullName);

        if (isset($dataFromBehat['id'])) {
            $classMetadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_NONE);
            $classMetadata->setIdGenerator(new \Doctrine\ORM\Id\AssignedGenerator());

            if ($parentEntity = $entityMetadata->getParentEntity()) {
                $parentMetadata = $this->entityManager->getClassMetadata($parentEntity);
                $parentMetadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_NONE);
                $parentMetadata->setIdGenerator(new \Doctrine\ORM\Id\AssignedGenerator());
            }

            $entityId = $dataFromBehat['id'];

            $entityId = $this->getEntityId($dataFromBehat['id'], $classMetadata);
            $entity = $this->entityFieldHandler->setId($entity, $entityId);

            unset($dataFromBehat['id']);
        } else {
            throw new \InvalidArgumentException(sprintf('You must provide id for %s entity', $entityFullName));
        }

        $entity = $this->processField($dataFromBehat, $entityMetadata, $entity, $classMetadata);

        $entity = $this->entityFieldHandler->handleRequiredFields($classMetadata, $entity);

        $this->entityCache->store($entity, $classMetadata->getTableName(), $entityId);

        if ($parentEntity = $entityMetadata->getParentEntity()) {
            $parentMetadata = $this->entityManager->getClassMetadata($parentEntity);

            $this->entityCache->store($entity, $parentMetadata->getTableName(), $entityId);
        }

        return $entity;
    }

    public function getParentClass($entityName)
    {
        $entityMetadata = $this->entities->getEntityMetadata($entityName);

        return $entityMetadata->getParentEntity();
    }

    private function processField($dataFromBehat, EntityMetadata $entityMetadata, $entity, ClassMetadata $classMetadata)
    {
        foreach ($dataFromBehat as $fieldName => $fieldValue) {
            if ($entityMetadata->hasHook($fieldName)) {
                $entity = $this->entityFieldHandler->handleHook(
                    $entity,
                    $entityMetadata->getHook(),
                    $fieldName,
                    $fieldValue
                );
            } elseif ($classMetadata->hasAssociation($fieldName)) {
                $associationMapping = $classMetadata->getAssociationMapping($fieldName);

                if (
                    $associationMapping['type'] === ClassMetadata::ONE_TO_MANY ||
                    $associationMapping['type'] === ClassMetadata::MANY_TO_MANY
                ) {

                    $entity = $this->entityFieldHandler->handleArrayAssociationField(
                        $classMetadata,
                        $fieldValue,
                        $entity,
                        $fieldName
                    );
                } else {
                    $entity = $this->entityFieldHandler->handleAssociationField(
                        $classMetadata,
                        $fieldValue,
                        $entity,
                        $fieldName
                    );
                }
            } elseif ($classMetadata->hasField($fieldName)) {
                $entity = $this->entityFieldHandler->handlePropertyField($fieldValue, $entity, $fieldName);
            } else {
                $entityName = $entityMetadata->getFullName();
                throw new \Exception("Field, relation or hook '$fieldName' does not exist for entity: $entityName\n");
            }
        }

        return $entity;
    }

    /**
     * @throws \Doctrine\ORM\Mapping\MappingException
     */
    private function getEntityId($entityId, ClassMetadata $classMetadata)
    {
        $identifierColumnMapping = $classMetadata->getFieldMapping($classMetadata->getSingleIdentifierColumnName());
        if (!empty($identifierColumnMapping) && $identifierColumnType = $identifierColumnMapping['type']) {
            switch ($identifierColumnType) {
                case 'integer':
                    return (int)$entityId;
            }
        }

        return $entityId;
    }
}
