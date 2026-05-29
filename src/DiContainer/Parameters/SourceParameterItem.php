<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Parameters;

use Kaspi\DiContainer\Exception\ParameterException;
use Kaspi\DiContainer\Interfaces\Exceptions\ParameterExceptionInterface;
use Kaspi\DiContainer\Interfaces\ResetInterface;
use Kaspi\DiContainer\Interfaces\SourceParametersMutableInterface;
use UnitEnum;

use function sprintf;

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
     *
     * @throws ParameterExceptionInterface
     */
    public function getResolved(): array|bool|float|int|string|UnitEnum|null
    {
        return $this->isResolved
            ? $this->resolved
            : throw new ParameterException(sprintf('Container parameter "%s" is not resolve yet.', $this->name));
    }

    public function reset(): void
    {
        $this->isResolved = false;
        unset($this->resolved);
    }
}
