<?php

namespace BehatFixturesLoader\EntityFactory;

use BehatFixturesLoader\EntityPopulator\EntityFakerPopulator;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use ReflectionClass;

class EntityFieldHandler
{
    const ARRAY_REGEXP = '/\[(.*)\]/';

    /**
     * @var EntityCache
     */
    private $entityCache;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $entityManager;

    public function __construct(EntityCache $entityCache, EntityManager $entityManager)
    {
        $this->entityCache = $entityCache;
        $this->entityManager = $entityManager;
    }

    public function setId($entity, $entityId)
    {
        return $this->setProperty($entity, 'id', $entityId);
    }

    /**
     * @param $classMetadata
     * @param $fieldValue
     * @param $entity
     * @param $fieldName
     * @return array
     */
    public function handleAssociationField(ClassMetadata $classMetadata, $fieldValue, $entity, $fieldName)
    {
        if ($fieldValue === '@null') {
            return $entity;
        }

        $targetEntityTableName = $this->getTargetEntityTableName($classMetadata, $fieldName);

        $targetValue = $this->entityCache->fetch($targetEntityTableName, $fieldValue);

        $entity = $this->setProperty($entity, $fieldName, $targetValue);

        return $entity;
    }


    public function handleArrayAssociationField(ClassMetadata $classMetadata, $fieldValue, $entity, $fieldName)
    {
        $targetValueIds = $this->processValue($fieldValue);
        $targetValue = [];

        $targetEntityTableName = $this->getTargetEntityTableName($classMetadata, $fieldName);

        foreach ($targetValueIds as $id) {
            $targetValue[] = $this->entityCache->fetch($targetEntityTableName, $id);
        }

        $entity = $this->setProperty($entity, $fieldName, $targetValue);

        return $entity;
    }

    /**
     * @param $fieldValue
     * @param $entity
     * @param $fieldName
     * @return mixed
     */
    public function handlePropertyField($fieldValue, $entity, $fieldName)
    {
        if ($fieldValue === '@null') {
            return $entity;
        }

        $targetValue = $this->processValue($fieldValue);
        $targetValue = $this->convertValue($entity, $fieldName, $targetValue);

        $entity = $this->setProperty($entity, $fieldName, $targetValue);

        return $entity;
    }

    /**
     * @param  ClassMetadata $classMetadata
     * @param $entity
     * @return mixed
     */
    public function handleRequiredFields(ClassMetadata $classMetadata, $entity)
    {
        $entityFields = $classMetadata->getFieldNames();

        foreach ($entityFields as $fieldName) {
            $fieldMapping = $classMetadata->getFieldMapping($fieldName);
            if ($classMetadata->getFieldValue($entity, $fieldName) === null
                && isset($fieldMapping['nullable'])
                && !$fieldMapping['nullable']
            ) {
                if ($fieldName === 'updatedAt' || $fieldName === 'createdAt') {
                    continue;
                }

                $populator = new EntityFakerPopulator($classMetadata);
                $entity = $populator->fillField($entity, $fieldName);
            }
        }

        return $entity;
    }

    public function handleHook($entity, $hook, $fieldName, $fieldValue)
    {
        $fieldValue = $this->processValue($fieldValue);

        return $hook->$fieldName($entity, $fieldValue);
    }

    /**
     * @param $entity
     * @param $property
     * @param $value
     * @return mixed
     */
    private function setProperty($entity, $property, $value)
    {
        $class = get_class($entity);

        $reflection = new ReflectionClass($class);

        $reflectionProperty = $reflection->getProperty($property);
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($entity, $value);
        $reflectionProperty->setAccessible(false);

        return $entity;
    }

    private function processValue($value)
    {
        if ($this->isArray($value)) {
            $value = $this->stringToArray($value);
        }

        return $value;
    }

    private function isArray($value)
    {
        return preg_match(self::ARRAY_REGEXP, $value);
    }

    private function stringToArray($string)
    {
        $string = str_replace(['[', ']'], '', $string);

        return explode(',', $string);
    }

    /**
     * @param ClassMetadata $classMetadata
     * @param $fieldName
     * @return string
     */
    private function getTargetEntityTableName(ClassMetadata $classMetadata, $fieldName)
    {
        $targetClass = $classMetadata->getAssociationTargetClass($fieldName);
        $targetClassMetadata = $this->entityManager->getClassMetadata($targetClass);
        $targetEntityTableName = $targetClassMetadata->getTableName();

        return $targetEntityTableName;
    }

    /**
     * @param $entity
     * @param $fieldName
     * @param $targetValue
     * @return mixed
     */
    private function convertValue($entity, $fieldName, $targetValue)
    {
        $metadata = $this->entityManager->getClassMetadata(get_class($entity));
        $type = $metadata->getTypeOfField($fieldName);
        switch ($type) {
            case 'boolean':
                $targetValue = is_bool($targetValue) ? (bool) $targetValue : $targetValue;
                break;
            case 'smallint':
            case 'integer':
            case 'bigint':
                $targetValue = is_numeric($targetValue) ? intval($targetValue) : $targetValue;
                break;
            case 'datetime':
            case 'date':
                $targetValue = new \DateTime($targetValue);
                break;
        }

        return $targetValue;
    }

}
