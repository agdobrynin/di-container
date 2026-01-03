<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Compiler;

use Kaspi\DiContainer\Compiler\CompilableDefinition\ValueEntry;
use Kaspi\DiContainer\Interfaces\Compiler\CompiledEntryInterface;
use Kaspi\DiContainer\Interfaces\Compiler\DiContainerDefinitionsInterface;
use Kaspi\DiContainer\Interfaces\Compiler\DiDefinitionTransformerInterface;
use Kaspi\DiContainer\Interfaces\Compiler\Exception\DefinitionCompileExceptionInterface;

use function array_key_last;
use function is_string;
use function preg_match;
use function preg_replace;
use function preg_replace_callback;
use function sprintf;
use function str_replace;
use function strrpos;
use function strtolower;
use function substr;
use function trim;

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
     * @param non-empty-string $id
     * @param non-empty-string $prefix
     *
     * @return non-empty-string
     */
    public static function convertContainerIdentifierToMethodName(string $id, string $prefix = 'resolve_'): string
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
            ? $prefix.'service'
            : $prefix.$name;
    }
}
