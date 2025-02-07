<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Interfaces\DiDefinition;

use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;

interface DiTaggedDefinitionAutowireInterface extends DiTaggedDefinitionInterface
{
    /**
     * Get priority for tag with extended parameters for php classes.
     *
     * @param non-empty-string $name
     * @param null|string      $defaultPriorityTagMethod     method return priority if not defined priority in tag option
     * @param bool             $requireDefaultPriorityMethod method for get priority is required
     */
    public function geTagPriority(string $name, ?string $defaultPriorityTagMethod = null, bool $requireDefaultPriorityMethod = false): null|int|string;

    /**
     * @throws AutowireExceptionInterface
     */
    public function getDefinition(): \ReflectionClass;

    public function setContainer(DiContainerInterface $container): static;
}
