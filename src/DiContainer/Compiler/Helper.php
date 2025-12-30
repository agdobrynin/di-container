<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Compiler;

use Exception;
use Kaspi\DiContainer\Compiler\CompilableDefinition\ValueEntry;
use Kaspi\DiContainer\Exception\DefinitionCompileException;
use Kaspi\DiContainer\Interfaces\Compiler\DiContainerDefinitionsInterface;
use Kaspi\DiContainer\Interfaces\Compiler\DiDefinitionTransformerInterface;
use Kaspi\DiContainer\Interfaces\Compiler\Exception\DefinitionCompileExceptionInterface;

use function array_key_last;
use function array_push;
use function bin2hex;
use function in_array;
use function is_string;
use function preg_match;
use function preg_replace;
use function preg_replace_callback;
use function random_bytes;
use function sprintf;
use function str_replace;
use function strrpos;
use function strtolower;
use function substr;
use function trim;

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
        DiContainerDefinitionsInterface $diContainerDefinitions,
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
            $compiledEntity = $transformer->transform($arg, $diContainerDefinitions, static fn (mixed $arg) => new ValueEntry($arg))
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
            try {
                $varName = $varName.'_'.bin2hex(random_bytes(5));
            } catch (Exception $e) {
                throw new DefinitionCompileException('Cannot generate random service variable name.', previous: $e);
            }
        }

        $varNames[] = $varName;

        return $varName;
    }

    /**
     * @return non-empty-string
     */
    public static function convertContainerIdentifierToMethodName(string $id): string
    {
        // Identifier may present as fully qualified class name. Take only class name.
        if (false !== ($pos = strrpos($id, '\\')) && isset($id[$pos + 1])) {
            $id = substr($id, $pos + 1);
        }

        /** @var string $name */
        $name = preg_replace_callback(
            '/([a-z])([A-Z])/',
            static fn (array $a) => $a[1].'_'.strtolower($a[2]),
            (string) preg_replace('/[^a-zA-Z0-9_\x7f-\xff]/', '.', $id)
        );
        $name = strtolower(trim(str_replace('.', '_', $name), '_'));

        return 1 !== preg_match('/^[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*$/', $name)
            ? 'resolve_service'
            : 'resolve_'.$name;
    }
}
