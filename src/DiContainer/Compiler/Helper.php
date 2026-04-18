<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Compiler;

use InvalidArgumentException;
use Kaspi\DiContainer\Compiler\CompilableDefinition\ValueEntry;
use Kaspi\DiContainer\Interfaces\Compiler\CompiledEntryInterface;
use Kaspi\DiContainer\Interfaces\Compiler\DiContainerDefinitionsInterface;
use Kaspi\DiContainer\Interfaces\Compiler\DiDefinitionTransformerInterface;
use Kaspi\DiContainer\Interfaces\Compiler\Exception\DefinitionCompileExceptionInterface;
use UnitEnum;

use function array_key_last;
use function get_debug_type;
use function is_array;
use function is_scalar;
use function is_string;
use function sprintf;
use function str_repeat;
use function var_export;

final class Helper
{
    /**
     * Build php expression for arguments, update statements into `$mainEntry`.
     *
     * @param mixed[]          $args
     * @param non-empty-string $containerVar
     *
     * @return non-empty-string php expression for arguments
     *
     * @throws DefinitionCompileExceptionInterface
     */
    public static function compileArguments(
        CompiledEntryInterface $mainEntry,
        string $containerVar,
        array $args,
        DiDefinitionTransformerInterface $transformer,
        DiContainerDefinitionsInterface $diContainerDefinitions,
        mixed $context = null
    ): string {
        $expression = '(';

        foreach ($args as $argIndexOrName => $arg) {
            $compiledEntity = $transformer->transform($arg, $diContainerDefinitions, static fn (mixed $arg) => new ValueEntry($arg))
                ->compile($containerVar, [...$mainEntry->getScopeVars(), $containerVar], $context)
            ;
            $mainEntry->addToScopeVars($compiledEntity->getScopeServiceVar(), ...$compiledEntity->getScopeVars());
            $mainEntry->addToStatements(...$compiledEntity->getStatements());

            $expression .= is_string($argIndexOrName)
                ? sprintf(PHP_EOL.'  %s: %s,', $argIndexOrName, $compiledEntity->getExpression())
                : sprintf(PHP_EOL.'  %s,', $compiledEntity->getExpression());
        }

        $expression .= null !== array_key_last($args)
            ? \PHP_EOL.')'
            : ')';

        return $expression;
    }

    /**
     * @return non-empty-string
     *
     * @throws InvalidArgumentException
     */
    public static function exportSimplestValues(mixed $value): string
    {
        if (null === $value || is_scalar($value) || $value instanceof UnitEnum) {
            // @phpstan-ignore return.type
            return $value instanceof UnitEnum && PHP_VERSION_ID < 80200
                ? '\\'.var_export($value, true)
                : var_export($value, true);
        }

        $exceptionMessage = 'Cannot export type "%s". Support only a scalar-type, null value, UnitEnum type or array with that types.';

        if (is_array($value)) {
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

                return $array_export($value);
            } catch (InvalidArgumentException $e) {
                throw new InvalidArgumentException(sprintf($exceptionMessage.' %s', get_debug_type($value), $e->getMessage()));
            }
        }

        throw new InvalidArgumentException(sprintf($exceptionMessage, get_debug_type($value)));
    }
}
