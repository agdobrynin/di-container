<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Compiler;

use Exception;
use Kaspi\DiContainer\DiDefinition\DiDefinitionValue;
use Kaspi\DiContainer\Exception\DiDefinitionCompileException;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionArgumentsInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionCompileInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionCompileExceptionInterface;

use function array_key_last;
use function array_push;
use function bin2hex;
use function in_array;
use function is_string;
use function random_bytes;
use function sprintf;

/**
 * @phpstan-import-type BindArgumentsType from DiDefinitionArgumentsInterface
 */
final class Helper
{
    /**
     * @param non-empty-string       $scopeServiceVariableName
     * @param BindArgumentsType      $args
     * @param non-empty-string       $containerVariableName
     * @param list<non-empty-string> $scopeVariableNames
     *
     * @throws DiDefinitionCompileExceptionInterface
     */
    public static function compileArguments(
        string $scopeServiceVariableName,
        array $args,
        string $containerVariableName,
        DiContainerInterface $container,
        array $scopeVariableNames,
        mixed $context = null
    ): CompiledEntry {
        while (in_array($scopeServiceVariableName, $scopeVariableNames, true)) {
            // TODO check max trying generate variable name?
            try {
                $scopeServiceVariableName = '$service_'.bin2hex(random_bytes(5));
            } catch (Exception $e) {
                throw new DiDefinitionCompileException(
                    'Cannot generate random service variable name.',
                    previous: $e
                );
            }
        }

        $scopeVariableNames[] = $scopeServiceVariableName;

        $expression = '(';
        $statements = '';

        foreach ($args as $argIndexOrName => $arg) {
            /** @var DiDefinitionCompileInterface $argToCompile */
            $argToCompile = $arg instanceof DiDefinitionCompileInterface
                ? $arg
                : new DiDefinitionValue($arg);

            $compiledEntity = $argToCompile->compile($containerVariableName, $container, $scopeVariableNames, $context);
            array_push($scopeVariableNames, ...$compiledEntity->getScopeVariables());

            if ('' !== $compiledEntity->getStatements()) {
                $statements .= $compiledEntity->getStatements();
                $argExpression = $compiledEntity->getScopeServiceVariableName();
            } else {
                $argExpression = $compiledEntity->getExpression();
            }

            $expression .= is_string($argIndexOrName)
                ? sprintf(PHP_EOL.'  %s: %s,', $argIndexOrName, $argExpression)
                : sprintf(PHP_EOL.'  %s,', $argExpression);
        }

        $expression .= null !== array_key_last($args)
            ? \PHP_EOL.')'
            : ')';

        return new CompiledEntry($expression, null, $statements, $scopeServiceVariableName, $scopeVariableNames);
    }
}
