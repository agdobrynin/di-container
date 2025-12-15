<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Compiler;

use Exception;
use Kaspi\DiContainer\Compiler\CompilableDefinition\ValueEntry;
use Kaspi\DiContainer\Exception\DefinitionCompileException;
use Kaspi\DiContainer\Interfaces\Compiler\DiDefinitionTransformerInterface;
use Kaspi\DiContainer\Interfaces\Compiler\Exception\DefinitionCompileExceptionInterface;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;

use function array_key_last;
use function array_push;
use function bin2hex;
use function in_array;
use function is_string;
use function random_bytes;
use function sprintf;

final class Helper
{
    /**
     * @param non-empty-string       $scopeServiceVariableName
     * @param mixed[]                $args
     * @param non-empty-string       $containerVariableName
     * @param list<non-empty-string> $scopeVariableNames
     *
     * @throws DefinitionCompileExceptionInterface
     */
    public static function compileArguments(
        DiDefinitionTransformerInterface $transformer,
        DiContainerInterface $container,
        string $containerVariableName,
        string $scopeServiceVariableName,
        array $scopeVariableNames,
        array $args,
        mixed $context = null
    ): CompiledEntry {
        while (in_array($scopeServiceVariableName, $scopeVariableNames, true)) {
            // TODO check max trying generate variable name?
            try {
                $scopeServiceVariableName = '$service_'.bin2hex(random_bytes(5));
            } catch (Exception $e) {
                throw new DefinitionCompileException('Cannot generate random service variable name.', previous: $e);
            }
        }

        $scopeVariableNames[] = $scopeServiceVariableName;

        $expression = '(';
        $statements = '';

        foreach ($args as $argIndexOrName => $arg) {
            $compiledEntity = $transformer->transform($arg, $container, static fn (mixed $arg) => new ValueEntry($arg))
                ->compile($containerVariableName, $scopeVariableNames, $context)
            ;
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
