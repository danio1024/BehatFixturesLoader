<?php

namespace BehatFixturesLoader\EntityFactory\Hooks;

abstract class Hook
{
    public static function init($array = [])
    {
        return (new \ReflectionClass(get_called_class()))->newInstanceArgs($array);
    }

    public function hasHook($name)
    {
        return method_exists($this, $name);
    }
}
