<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Compiler\CompilableDefinition;

use InvalidArgumentException;
use Kaspi\DiContainer\Compiler\CompiledEntry;
use Kaspi\DiContainer\Exception\DefinitionCompileException;
use Kaspi\DiContainer\Interfaces\Compiler\CompilableDefinitionInterface;
use Kaspi\DiContainer\Interfaces\Compiler\CompiledEntryInterface;
use UnitEnum;

use function get_debug_type;
use function is_array;
use function is_scalar;
use function sprintf;
use function str_repeat;
use function var_export;

final class ValueEntry implements CompilableDefinitionInterface
{
    public function __construct(private readonly mixed $definition) {}

    public function compile(string $containerVar, array $scopeVars = [], mixed $context = null): CompiledEntryInterface
    {
        if (null === $this->definition || is_scalar($this->definition) || $this->definition instanceof UnitEnum) {
            if ($this->definition instanceof UnitEnum) {
                /** @var non-falsy-string $returnType */
                $returnType = '\\'.get_debug_type($this->definition);
                $expression = PHP_VERSION_ID < 80200
                    ? '\\'.var_export($this->definition, true)
                    : var_export($this->definition, true);
            } else {
                /** @var non-falsy-string $returnType */
                $returnType = get_debug_type($this->definition);
                $expression = var_export($this->definition, true);
            }

            return new CompiledEntry(expression: $expression, returnType: $returnType);
        }

        $exceptionMessage = 'Cannot compile definition type "%s". Support only a scalar-type, null value, UnitEnum type or array with that types.';

        if (is_array($this->definition)) {
            try {
                $tabLevel = static fn (int $level): string => 0 === $level ? '' : str_repeat('  ', $level);

                $array_export = static function (array $items, int $level = 0) use (&$array_export, $tabLevel): string {
                    $output = '['.PHP_EOL;

                    foreach ($items as $key => $value) {
                        if (is_array($value)) {
                            $output .= $tabLevel($level + 1).var_export($key, true).' => ';
                            $output .= $array_export($value, $level + 1);
                        } else {
                            if (null !== $value && !is_scalar($value) && !($value instanceof UnitEnum)) {
                                throw new InvalidArgumentException(sprintf('The value in array is invalid type "%s".', get_debug_type($value)));
                            }

                            $var = $value instanceof UnitEnum && PHP_VERSION_ID < 80200
                                ? '\\'.var_export($value, true)
                                : var_export($value, true);
                            $expression = var_export($key, true).' => '.$var.','.PHP_EOL;
                            $output .= $tabLevel($level + 1).$expression;
                        }
                    }

                    return 0 === $level
                        ? $output.']'
                        : $output.$tabLevel($level).'],'.PHP_EOL;
                };

                return new CompiledEntry(expression: $array_export($this->definition), returnType: 'array');
            } catch (InvalidArgumentException $e) {
                throw new DefinitionCompileException(sprintf($exceptionMessage.' %s', get_debug_type($this->definition), $e->getMessage()));
            }
        }

        throw new DefinitionCompileException(sprintf($exceptionMessage, get_debug_type($this->definition)));
    }

    public function getDiDefinition(): mixed
    {
        return $this->definition;
    }
}
