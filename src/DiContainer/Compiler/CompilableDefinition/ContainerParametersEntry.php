<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Compiler\CompilableDefinition;

use Kaspi\DiContainer\Compiler\CompiledEntry;
use Kaspi\DiContainer\Compiler\Helper;
use Kaspi\DiContainer\Enum\InvalidBehaviorCompileEnum;
use Kaspi\DiContainer\Exception\DefinitionCompileException;
use Kaspi\DiContainer\Interfaces\Compiler\CompilableDefinitionInterface;
use Kaspi\DiContainer\Interfaces\Compiler\CompiledEntryInterface;
use Kaspi\DiContainer\Interfaces\Compiler\DiContainerDefinitionsInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ParameterExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ParameterNotFoundExceptionInterface;
use Kaspi\DiContainer\Interfaces\SourceParametersMutableInterface;
use Throwable;

use function sprintf;
use function var_export;

use const PHP_EOL;

final class ContainerParametersEntry implements CompilableDefinitionInterface
{
    public function __construct(
        private readonly DiContainerDefinitionsInterface $diContainerDefinitions,
        private readonly InvalidBehaviorCompileEnum $invalidBehaviorCompile,
    ) {}

    public function compile(string $containerVar, array $scopeVars = [], mixed $context = null): CompiledEntryInterface
    {
        $fallback = InvalidBehaviorCompileEnum::RuntimeContainerException === $this->invalidBehaviorCompile
            ? static fn (string $name, Throwable $e) => $e
            : null;

        $parameters = $this->getDiDefinition()->parameters($fallback);

        $yieldParameters = '';

        do {
            try {
                $name = $parameters->key();

                if (null === $name) {
                    break;
                }

                $value = $parameters->current();

                $preparedValue = $value instanceof ParameterExceptionInterface || $value instanceof ParameterNotFoundExceptionInterface
                    ? sprintf('new \%s(message: %s)', $value::class, var_export($value->getMessage(), true))
                    : Helper::exportSimplestValues($value);

                $yieldParameters .= sprintf('  yield %s => %s;'.PHP_EOL, var_export($name, true), $preparedValue);

                $parameters->next();
            } catch (ParameterExceptionInterface|ParameterNotFoundExceptionInterface $e) {
                throw new DefinitionCompileException(
                    'Cannot compile container parameters.',
                    previous: $e
                );
            }
        } while ($parameters->valid());

        if ('' !== $yieldParameters) {
            $expression = <<< DEFERRED
new \\Kaspi\\DiContainer\\Parameters\\DeferredSourceParameters(static function () {
{$yieldParameters}})
DEFERRED;

            return (new CompiledEntry())
                ->setExpression($expression)
            ;
        }

        return (new CompiledEntry())
            ->setExpression('new \Kaspi\DiContainer\Parameters\ImmediateSourceParameters()')
        ;
    }

    public function getDiDefinition(): SourceParametersMutableInterface
    {
        return $this->diContainerDefinitions
            ->getContainer()
            ->parameters()
        ;
    }
}
