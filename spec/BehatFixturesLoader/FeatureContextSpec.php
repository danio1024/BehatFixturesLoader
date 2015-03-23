<?php

namespace spec\Meddo\Behat\Context;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class FeatureContextSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Meddo\Behat\Context\FeatureContext');
    }
}
