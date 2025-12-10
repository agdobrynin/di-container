<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition;

use InvalidArgumentException;
use Kaspi\DiContainer\Compiler\CompiledEntry;
use Kaspi\DiContainer\Exception\DiDefinitionCompileException;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\CompiledEntryInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionCompileInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionTagArgumentInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiTaggedDefinitionInterface;
use Kaspi\DiContainer\Traits\TagsTrait;
use UnitEnum;
use function array_walk_recursive;
use function get_debug_type;
use function is_array;
use function is_scalar;
use function sprintf;
use function var_export;

final class DiDefinitionValue implements DiDefinitionInterface, DiDefinitionTagArgumentInterface, DiTaggedDefinitionInterface, DiDefinitionCompileInterface
{
    use TagsTrait;

    public function __construct(private readonly mixed $definition) {}

    public function getDefinition(): mixed
    {
        return $this->definition;
    }

    /**
     * @throws void
     */
    public function resolve(DiContainerInterface $container, mixed $context = null): mixed
    {
        return $this->definition;
    }

    public function compile(string $containerVariableName, DiContainerInterface $container): CompiledEntryInterface
    {
        if (null === $this->definition || is_scalar($this->definition) || $this->definition instanceof UnitEnum) {
            return new CompiledEntry(var_export($this->definition, true), '', [], true, get_debug_type($this->definition));
        }

        if (is_array($this->definition)) {
            try {
                $tmp = $this->definition;

                array_walk_recursive($tmp, static function (mixed $item) {
                    if (null !== $item && !is_scalar($item) && !($item instanceof UnitEnum)) {
                        throw new InvalidArgumentException(sprintf('The value in array is invalid type "%s".', get_debug_type($item)));
                    }
                });

                return new CompiledEntry(var_export($this->definition, true), '', [], true, 'array');
            } catch (InvalidArgumentException $exceptionInArray) {
            }
        }

        $causedBy = isset($exceptionInArray)
            ? ' Caused by: '.$exceptionInArray->getMessage()
            : '';

        throw new DiDefinitionCompileException(
            sprintf('Cannot compile definition type "%s". Support only a scalar-type, null value, UnitEnum type or array with that types.'.$causedBy, get_debug_type($this->definition)),
            previous: $exceptionInArray ?? null,
        );
    }
}
