<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Attributes;

use Attribute;
use Kaspi\DiContainer\Exception\AutowireAttributeException;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionArgumentsInterface;

use function is_array;
use function is_string;
use function sprintf;
use function var_export;

/**
 * @phpstan-import-type DiDefinitionType from DiDefinitionArgumentsInterface
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
final class DiFactory
{
    /**
     * @param array{0: class-string|non-empty-string, 1: non-empty-string}|class-string|non-empty-string $definition
     * @param array<non-empty-string|non-negative-int, DiDefinitionType|mixed>                           $arguments
     */
    public function __construct(
        public readonly array|string $definition,
        public readonly ?bool $isSingleton = null,
        public readonly array $arguments = [],
    ) {
        if (is_array($this->definition)
            && (
                !isset($this->definition[0], $this->definition[1])
                || !(is_string($this->definition[0]) && is_string($this->definition[1]))
                || '' === $this->definition[0] || '' === $this->definition[1]
            )
        ) {
            throw new AutowireAttributeException(
                sprintf('Invalid parameter for attribute #[%s]. The array values must be provided as none empty string and numeric index. Got: "%s".', self::class, var_export($this->definition, true))
            );
        }
        if ('' === $this->definition) { // @phpstan-ignore identical.alwaysFalse
            throw new AutowireAttributeException(
                sprintf('Invalid parameter for attribute #[%s]. The definition must be provided as none empty string.', self::class)
            );
        }
    }
}
