<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionAutowire\Fixtures;

use Kaspi\DiContainer\Attributes\Setup;
use Kaspi\DiContainer\DiDefinition\DiDefinitionGet as DiGet;
use Kaspi\DiContainer\DiDefinition\DiDefinitionValue as DiValue;

final class SetupByAttributeWithArgumentAsReference
{
    public SomeClass $dependencyAutoResolve;
    private ?SomeClass $someClass = null;
    private ?string $anyAsContainerIdentifier = null;
    private ?string $anyAsEscapedString = null;

    private ?string $anyAsString = null;

    /**
     * Will return SomeClass::SomeClass.
     */
    public function getSomeClass(): ?SomeClass
    {
        return $this->someClass;
    }

    /**
     * Will return value from the container mock object.
     */
    public function getAnyAsContainerIdentifier(): ?string
    {
        return $this->anyAsContainerIdentifier;
    }

    /**
     * Will return string "@la-la-la".
     */
    public function getAnyAsEscapedString(): ?string
    {
        return $this->anyAsEscapedString;
    }

    /**
     * Will return "la-la-la".
     */
    public function getAnyAsString(): ?string
    {
        return $this->anyAsString;
    }

    #[Setup(someClass: new DiGet('services.some_class'))]
    public function setSomeClassAsContainerIdentifier($someClass, SomeClass $class): void
    {
        $this->someClass = $someClass;
        $this->dependencyAutoResolve = $class;
    }

    #[Setup(any: new DiGet('services.any_string'))]
    public function setAnyAsContainerIdentifier(string $any): void
    {
        $this->anyAsContainerIdentifier = $any;
    }

    #[Setup(any: new DiValue('@@la-la-la'))]
    public function setAnyAsEscapedString(string $any): void
    {
        $this->anyAsEscapedString = $any;
    }

    #[Setup(anyAsString: 'la-la-la')]
    public function setAnyAsString(string $anyAsString): void
    {
        $this->anyAsString = $anyAsString;
    }
}
