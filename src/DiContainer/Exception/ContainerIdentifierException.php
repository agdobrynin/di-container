<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Exception;

use Kaspi\DiContainer\Interfaces\Exceptions\ContainerIdentifierExceptionInterface;
use Throwable;

final class ContainerIdentifierException extends ContainerException implements ContainerIdentifierExceptionInterface
{
    public function __construct(
        private readonly mixed $identifier,
        private readonly mixed $definition,
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        if ('' === $message) {
            $message = 'Definition identifier must be a non-empty string.';
        }

        parent::__construct($message, $code, $previous);
    }

    public function getIdentifier(): mixed
    {
        return $this->identifier;
    }

    public function getDefinition(): mixed
    {
        return $this->definition;
    }
}
