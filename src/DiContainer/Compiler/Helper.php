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
use function sprintf;

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
}
