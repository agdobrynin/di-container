<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Exception;

use Exception;
use Kaspi\DiContainer\Interfaces\Exceptions\DefinitionsLoaderExceptionInterface;
use Throwable;

class DefinitionsLoaderException extends Exception implements DefinitionsLoaderExceptionInterface
{
    /** @var array<non-negative-int|string, mixed> */
    private readonly array $context;

    public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null, mixed ...$context)
    {
        /**
         * @phpstan-var array<string|non-negative-int, mixed> $context
         */
        $this->context = $context;
        parent::__construct($message, $code, $previous);
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
