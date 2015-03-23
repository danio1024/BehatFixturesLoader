<?php

namespace spec\BehatFixturesLoader\EntityPopulator;

use Doctrine\ORM\Mapping\ClassMetadata;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class EntityFakerPopulatorSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType('BehatFixturesLoader\EntityPopulator\EntityFakerPopulator');
    }

    public function let(ClassMetadata $metadata)
    {
        $this->beConstructedWith($metadata);
    }

    public function it_fills_entity_field(
        ClassMetadata $metadata,
        \stdClass $object
    )
    {
        $fieldName = 'city';

        $fieldsTypes = [
            'boolean' => 'bool',
            'smallint' => 'integer',
            'integer' => 'integer',
            'bigint' => 'integer',
            'float' => 'float',
            'string' => 'string',
            'text' => 'string',
            'datetime' => 'datetime',
        ];

        foreach ($fieldsTypes as $type => $argumentType) {
            $metadata->getTypeOfField($fieldName)->shouldBeCalled()->willReturn($type);
            $metadata->setFieldValue($object, $fieldName, Argument::type($argumentType))->shouldBeCalled();
            $this->fillField($object, $fieldName)->shouldReturn($object);
        }
    }
}