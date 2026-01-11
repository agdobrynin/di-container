<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Compiler;

use Generator;
use InvalidArgumentException;
use Kaspi\DiContainer\Compiler\CompilableDefinition\ValueEntry;
use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\Enum\InvalidBehaviorCompileEnum;
use Kaspi\DiContainer\Exception\CompiledContainerException;
use Kaspi\DiContainer\Exception\DefinitionCompileException;
use Kaspi\DiContainer\Exception\InvalidDefinitionCompileException;
use Kaspi\DiContainer\Interfaces\Compiler\CompiledContainerFQN;
use Kaspi\DiContainer\Interfaces\Compiler\CompiledEntryInterface;
use Kaspi\DiContainer\Interfaces\Compiler\ContainerCompilerInterface;
use Kaspi\DiContainer\Interfaces\Compiler\DiContainerDefinitionsInterface;
use Kaspi\DiContainer\Interfaces\Compiler\DiDefinitionTransformerInterface;
use Kaspi\DiContainer\Interfaces\Compiler\Exception\DefinitionCompileExceptionInterface;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Psr\Container\ContainerInterface;
use Throwable;

use function array_keys;
use function array_unshift;
use function get_debug_type;
use function ltrim;
use function ob_get_clean;
use function ob_start;
use function preg_match;
use function sprintf;
use function strrpos;
use function substr;
use function var_export;

final class ContainerCompiler implements ContainerCompilerInterface
{
    /**
     * Array key internal getter method name.
     * Each method name is converted to lowercase.
     *
     *      [
     *          'resolve_service_one' => [
     *              0 => 'App\\Services\\ServiceOne',
     *              1 => $compiledEntry,
     *          ],
     *      ]
     *
     * The value of array element has two items:
     * - index 0 – container identifier.
     * - index 1 – compiled entry.
     *
     * @var non-empty-array<non-empty-string, array{0: non-empty-string, 1:CompiledEntryInterface}>
     */
    private array $mapServiceMethodToContainerId;

    private CompiledContainerFQN $compiledContainerFQN;

    /**
     * @param class-string $containerClass container class as fully qualified name
     */
    public function __construct(
        private readonly string $containerClass,
        private readonly DiContainerDefinitionsInterface $diContainerDefinitions,
        private readonly DiDefinitionTransformerInterface $definitionTransform,
        private readonly InvalidBehaviorCompileEnum $invalidBehaviorCompile,
    ) {}

    public function getContainerFQN(): CompiledContainerFQN
    {
        if (isset($this->compiledContainerFQN)) {
            return $this->compiledContainerFQN;
        }

        $pos = strrpos($this->containerClass, '\\');

        /** @var non-empty-string $class */
        $class = false === $pos ? $this->containerClass : substr($this->containerClass, $pos + 1);
        $namespace = false === $pos ? '' : ltrim(substr($this->containerClass, 0, $pos), '\\');

        if (1 !== preg_match('/^[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*$/', $class)) {
            throw new InvalidArgumentException(
                sprintf('The container class name "%s" is invalid. Got fully qualified class name: "%s".', $class, $this->containerClass)
            );
        }

        if ('' !== $namespace && 1 !== preg_match('/^(?:[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*+\\\)++$/', $namespace.'\\')) {
            throw new InvalidArgumentException(
                sprintf('The namespace "%s" in container class name must be compatible with PSR-4. Got fully qualified class name: "%s".', $namespace, $this->containerClass)
            );
        }

        return $this->compiledContainerFQN = new class($namespace, $class) implements CompiledContainerFQN {
            private string $fqn;

            /** @param non-empty-string $class */
            public function __construct(private readonly string $namespace, private readonly string $class) {}

            public function getNamespace(): string
            {
                return $this->namespace;
            }

            public function getClass(): string
            {
                return $this->class;
            }

            public function getFQN(): string
            {
                // @phpstan-ignore return.type
                return $this->fqn ??= '' !== $this->namespace
                    ? '\\'.$this->namespace.'\\'.$this->class
                    : '\\'.$this->class;
            }
        };
    }

    public function compile(): string
    {
        /** @var null|non-empty-string $serviceMethodUnique */
        $serviceMethodUnique = null;
        $serviceSuffix = 0;
        $definitions = $this->containerDefinitions();

        while ($definitions->valid()) {
            try {
                /** @var CompiledEntryInterface|InvalidDefinitionCompileException|mixed $definition */
                $definition = $definitions->current();
                $id = $definitions->key();

                if ($definition instanceof InvalidDefinitionCompileException) {
                    throw $definition;
                }

                $compiledEntity = $definition instanceof CompiledEntryInterface
                    ? $definition
                    : $this->definitionTransform
                        ->transform($definition, $this->diContainerDefinitions)
                        ->compile('$this', context: $definition)
                ;
            } catch (DefinitionCompileExceptionInterface $e) {
                $exception = new DefinitionCompileException(
                    sprintf('Cannot compile definition type "%s" for container identifier "%s".', get_debug_type($definition), $id),
                    previous: $e
                );

                if (InvalidBehaviorCompileEnum::ExceptionOnCompile === $this->invalidBehaviorCompile) {
                    throw $exception;
                }

                $compiledEntity = $this->compiledExceptionStack($exception, $id);
            }

            $serviceMethod = Helper::convertContainerIdentifierToMethodName($id);

            while (isset($this->mapServiceMethodToContainerId[$serviceMethodUnique ?? $serviceMethod])) {
                ++$serviceSuffix;
                $serviceMethodUnique = $serviceMethod.$serviceSuffix;
            }

            $this->mapServiceMethodToContainerId[$serviceMethodUnique ?? $serviceMethod] = [$id, $compiledEntity];

            $serviceSuffix = 0;
            $serviceMethodUnique = null;

            $definitions->next();
        }

        ob_start();

        require __DIR__.'/template.php';

        return (string) ob_get_clean();
    }

    /**
     * @return Generator<non-empty-string, CompiledEntryInterface|InvalidDefinitionCompileException|mixed>
     */
    private function containerDefinitions(): Generator
    {
        $this->diContainerDefinitions->reset();

        $compiledClassFQN = $this->getContainerFQN()->getFQN();
        $containerCompiledEntry = new CompiledEntry(expression: '$this', returnType: $compiledClassFQN);

        $predefinedCompiledEntry = [
            ContainerInterface::class => $containerCompiledEntry,
            DiContainerInterface::class => $containerCompiledEntry,
            DiContainer::class => $containerCompiledEntry,
            $compiledClassFQN => $containerCompiledEntry,
        ];

        // exclude from container already compiled entries
        $this->diContainerDefinitions->excludeContainerIdentifier(...array_keys($predefinedCompiledEntry));

        yield from $predefinedCompiledEntry;

        yield from $this->diContainerDefinitions->getDefinitions(static function (string $id, DefinitionCompileExceptionInterface $e) {
            return new InvalidDefinitionCompileException(
                sprintf('Invalid definition getting via container identifier "%s".', $id),
                previous: $e,
            );
        });
    }

    private function compiledExceptionStack(Throwable $e, string $containerIdentifier): CompiledEntryInterface
    {
        // Structure related \Kaspi\DiContainer\Exception\ContainerCompileException::$exceptionStack
        /** @var list<array{
         *     exceptionType: string,
         *     message: string,
         *     file: string,
         *     line: int,
         *     code: int,
         *     trace_as_string: string
         * }> $exceptionStack
         */
        $exceptionStack = [];
        $prev = $e;

        do {
            array_unshift(
                $exceptionStack,
                [
                    'exceptionType' => get_debug_type($prev),
                    'message' => $prev->getMessage(),
                    'file' => $prev->getFile(),
                    'line' => $prev->getLine(),
                    'code' => $prev->getCode(),
                    'trace_as_string' => $prev->getTraceAsString(),
                ]
            );
        } while (null !== ($prev = $prev->getPrevious()));

        $exceptionStackEntry = (new ValueEntry($exceptionStack))->compile('$this');

        $compiledMainExceptionEntity = (new CompiledEntry(isSingleton: null, returnType: 'never'))
            ->addToStatements(...$exceptionStackEntry->getStatements())
        ;

        $compiledMainExceptionEntity->addToStatements(
            sprintf('%s = %s', $exceptionStackEntry->getScopeServiceVar(), $exceptionStackEntry->getExpression())
        );

        $message = sprintf('The definition was not compiled for the container identifier %s. Function %s::getExceptionStack() return exception stack.', var_export($containerIdentifier, true), CompiledContainerException::class);

        $expression = sprintf(
            'throw new \Kaspi\DiContainer\Exception\CompiledContainerException(message: %s, exceptionStack: %s)',
            var_export($message, true),
            $exceptionStackEntry->getScopeServiceVar(),
        );

        return $compiledMainExceptionEntity->setExpression($expression);
    }
}
