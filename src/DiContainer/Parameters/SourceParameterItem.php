<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Parameters;

use Kaspi\DiContainer\Interfaces\ResetInterface;
use Kaspi\DiContainer\Interfaces\SourceParametersMutableInterface;
use UnitEnum;

/**
 * @phpstan-import-type SourceParameterType from SourceParametersMutableInterface
 */
final class SourceParameterItem implements ResetInterface
{
    private bool $isResolved = false;

    /**
     * @var SourceParameterType
     */
    private array|bool|float|int|string|UnitEnum|null $resolved;

    /**
     * @param non-empty-string $name
     */
    public function __construct(
        public readonly string $name,
        public readonly mixed $src,
        public readonly bool $isMutable,
    ) {}

    public function isResolved(): bool
    {
        return $this->isResolved;
    }

    /**
     * @param SourceParameterType $value
     */
    public function setResolved(array|bool|float|int|string|UnitEnum|null $value): self
    {
        $this->resolved = $value;
        $this->isResolved = true;

        return $this;
    }

    /**
     * @return SourceParameterType
     */
    public function getResolved(): array|bool|float|int|string|UnitEnum|null
    {
        return $this->resolved;
    }

    public function reset(): void
    {
        $this->isResolved = false;
        unset($this->resolved);
    }
}
