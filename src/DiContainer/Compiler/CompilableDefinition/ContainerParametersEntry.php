<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Compiler\CompilableDefinition;

use InvalidArgumentException;
use Kaspi\DiContainer\Compiler\CompiledEntry;
use Kaspi\DiContainer\Compiler\Helper;
use Kaspi\DiContainer\Enum\InvalidBehaviorCompileEnum;
use Kaspi\DiContainer\Exception\DefinitionCompileException;
use Kaspi\DiContainer\Exception\ParameterException;
use Kaspi\DiContainer\Interfaces\Compiler\CompilableDefinitionInterface;
use Kaspi\DiContainer\Interfaces\Compiler\CompiledEntryInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ParameterExceptionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ParameterNotFoundExceptionInterface;
use Kaspi\DiContainer\Interfaces\SourceParametersMutableInterface;
use Kaspi\DiContainer\Parameters\DeferredSourceParameters;
use Kaspi\DiContainer\Parameters\ImmediateSourceParameters;
use Throwable;

use function sprintf;
use function var_export;

use const PHP_EOL;

final class ContainerParametersEntry implements CompilableDefinitionInterface
{
    public function __construct(
        private readonly SourceParametersMutableInterface $definition,
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

                $preparedValue = $this->prepareParameterValue($name, $parameters->current());

                $yieldParameters .= sprintf('  yield %s => %s;'.PHP_EOL, var_export($name, true), $preparedValue);

                $parameters->next();
            } catch (ParameterExceptionInterface|ParameterNotFoundExceptionInterface $e) {
                throw new DefinitionCompileException(
                    sprintf('Cannot compile container parameters. Reason by: %s', $e->getMessage()),
                    previous: $e
                );
            }
        } while ($parameters->valid());

        if ('' !== $yieldParameters) {
            $expression = sprintf('new \%s(static function () {%s})', DeferredSourceParameters::class, PHP_EOL.$yieldParameters);

            return (new CompiledEntry())
                ->setExpression($expression)
            ;
        }

        $expression = sprintf('new \%s()', ImmediateSourceParameters::class);

        return (new CompiledEntry())
            ->setExpression($expression)
        ;
    }

    public function getDiDefinition(): SourceParametersMutableInterface
    {
        return $this->definition;
    }

    private function prepareParameterValue(string $name, mixed $value): string
    {
        if ($value instanceof ParameterExceptionInterface || $value instanceof ParameterNotFoundExceptionInterface) {
            return sprintf('new \%s(message: %s)', $value::class, var_export($value->getMessage(), true));
        }

        try {
            return Helper::exportSimplestValues($value);
        } catch (InvalidArgumentException $e) {
            if (InvalidBehaviorCompileEnum::RuntimeContainerException === $this->invalidBehaviorCompile) {
                $message = sprintf('Cannot compile container parameter "%s". Reason by: %s', $name, $e->getMessage());

                return sprintf('new \%s(message: %s)', ParameterException::class, var_export($message, true));
            }

            throw new DefinitionCompileException(
                message: sprintf('Cannot compile container parameter "%s". Reason by: %s', $name, $e->getMessage()),
                previous: $e,
            );
        }
    }
}
