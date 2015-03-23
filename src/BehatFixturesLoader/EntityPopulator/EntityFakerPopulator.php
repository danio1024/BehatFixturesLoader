<?php

namespace BehatFixturesLoader\EntityPopulator;

use Doctrine\ORM\Mapping\ClassMetadata;
use Faker\Factory as FakerFactory;
use Faker\ORM\Doctrine\ColumnTypeGuesser;

class EntityFakerPopulator
{
    public function __construct(ClassMetadata $class)
    {
        $this->class = $class;
    }

    public function fillField($entity, $fieldName, $insertedEntities = [])
    {
        $format = $this->guessFieldFormat($fieldName);

        $value = is_callable($format) ? $format($insertedEntities, $entity) : $format;
        $this->class->setFieldValue($entity, $fieldName, $value);

        return $entity;
    }

    /**
     * @param $fieldName
     * @return callable|null
     */
    private function guessFieldFormat($fieldName)
    {
        $faker = FakerFactory::create('pl_PL');
        $guesser = new ColumnTypeGuesser($faker);
        $format = $guesser->guessFormat($fieldName, $this->class);

        return $format;
    }

} 