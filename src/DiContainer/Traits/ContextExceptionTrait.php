<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Traits;

trait ContextExceptionTrait
{
    /** @var array<non-negative-int|string, mixed> */
    private array $context = [];

    /**
     * @return array<non-negative-int|string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }

    public function setContext(mixed ...$context): self
    {
        /**
         * @phpstan-var array<string|non-negative-int, mixed> $context
         */
        $this->context = $context;

        return $this;
    }
}
