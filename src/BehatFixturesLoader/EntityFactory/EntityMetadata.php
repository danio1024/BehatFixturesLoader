<?php

namespace BehatFixturesLoader\EntityFactory;

class EntityMetadata
{
    private $fullName;
    private $hook;
    private $parentEntity = null;

    public function __construct($fullName)
    {
        $this->fullName = $fullName;
    }

    /**
     * @param mixed $fullName
     */
    public function setFullName($fullName)
    {
        $this->fullName = $fullName;
    }

    /**
     * @return mixed
     */
    public function getFullName()
    {
        return $this->fullName;
    }

    /**
     * @param mixed $hook
     */
    public function setHook($hook)
    {
        $this->hook = $hook;
    }

    /**
     * @return mixed
     */
    public function getHook()
    {
        return $this->hook;
    }

    public function hasHook($fieldName)
    {
        if ($this->getHook()) {
            return $this->getHook()->hasHook($fieldName);
        }

        return false;
    }

    public function setParentEntity($parentEntity)
    {
        $this->parentEntity = $parentEntity;
    }

    public function getParentEntity()
    {
        return $this->parentEntity;
    }
}
