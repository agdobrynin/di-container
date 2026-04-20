<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition;

use Kaspi\DiContainer\Exception\DiDefinitionException;

use function get_debug_type;
use function is_string;
use function sprintf;

abstract class DiDefinitionParameterWithContextAbstract
{
    protected ?string $context = null;

    public function setContext(?string $context): static
    {
        $this->context = $context;

        return $this;
    }

    public function getContext(): ?string
    {
        return $this->context;
    }

    abstract public function getDefinition(): string;

    /**
     * @return non-empty-string
     *
     * @throws DiDefinitionException
     */
    protected function nameWithContext(mixed $context): string
    {
        if ('' !== $this->getDefinition()) {
            return $this->getDefinition();
        }

        if (is_string($this->context) && '' !== $this->context) {
            return $this->context;
        }

        if (is_string($context) && '' !== $context) {
            return $context;
        }

        throw new DiDefinitionException(
            sprintf('Parameter name must be non-empty string. Parameter name my be set as $context in DiDefinitionParameter::resolve() or in DiDefinitionParameter::setContext(). Current $context type "%s".', get_debug_type($context))
        );
    }
}
