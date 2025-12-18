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
use function preg_match;
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
        $scopeServiceVariableName = self::genUniqueVarName($scopeServiceVariableName, $containerVariableName, $scopeVariableNames);

        $expression = '(';
        $statements = '';

        foreach ($args as $argIndexOrName => $arg) {
            $compiledEntity = $transformer->transform($arg, $container, static fn (mixed $arg) => new ValueEntry($arg))
                ->compile($containerVariableName, $scopeVariableNames, $context)
            ;
            array_push($scopeVariableNames, ...$compiledEntity->getScopeVariables());

            $statements .= $compiledEntity->getStatements();

            $expression .= is_string($argIndexOrName)
                ? sprintf(PHP_EOL.'  %s: %s,', $argIndexOrName, $compiledEntity->getExpression())
                : sprintf(PHP_EOL.'  %s,', $compiledEntity->getExpression());
        }

        $expression .= null !== array_key_last($args)
            ? \PHP_EOL.')'
            : ')';

        return new CompiledEntry($expression, null, $statements, $scopeServiceVariableName, $scopeVariableNames);
    }

    /**
     * @param non-empty-string       $varName
     * @param non-empty-string       $containerVariableName
     * @param list<non-empty-string> $varNames
     *
     * @return non-empty-string
     */
    public static function genUniqueVarName(string $varName, string $containerVariableName, array &$varNames): string
    {
        if (1 !== preg_match('/^\$[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*$/', $varName)) {
            throw new DefinitionCompileException(
                sprintf('Variable name "%s" is invalid. Variables in PHP are represented by a dollar sign followed by the name.', $varName)
            );
        }

        while (in_array($varName, $varNames, true) && $containerVariableName !== $varName) {
            // TODO check max trying generate variable name?
            try {
                $varName = $varName.'_'.bin2hex(random_bytes(5));
            } catch (Exception $e) {
                throw new DefinitionCompileException('Cannot generate random service variable name.', previous: $e);
            }
        }

        $varNames[] = $varName;

        return $varName;
    }
}
