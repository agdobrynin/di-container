<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Compiler;

use Kaspi\DiContainer\Compiler\CompilableDefinition\ValueEntry;
use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\Enum\InvalidBehaviorCompileEnum;
use Kaspi\DiContainer\Exception\CompiledContainerException;
use Kaspi\DiContainer\Exception\DefinitionCompileException;
use Kaspi\DiContainer\Interfaces\Compiler\CompiledContainerFQN;
use Kaspi\DiContainer\Interfaces\Compiler\CompiledEntryInterface;
use Kaspi\DiContainer\Interfaces\Compiler\ContainerCompilerInterface;
use Kaspi\DiContainer\Interfaces\Compiler\DiContainerDefinitionsInterface;
use Kaspi\DiContainer\Interfaces\Compiler\DiDefinitionTransformerInterface;
use Kaspi\DiContainer\Interfaces\Compiler\Exception\DefinitionCompileExceptionInterface;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Psr\Container\ContainerInterface;
use RuntimeException;
use Throwable;

use function array_unshift;
use function error_get_last;
use function fclose;
use function file_exists;
use function fwrite;
use function get_debug_type;
use function is_dir;
use function is_readable;
use function is_writable;
use function ltrim;
use function ob_get_clean;
use function ob_start;
use function realpath;
use function rename;
use function sprintf;
use function stream_get_meta_data;
use function strrpos;
use function substr;
use function tmpfile;
use function var_export;

use const DIRECTORY_SEPARATOR;

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

    /** @var non-empty-string */
    private string $normalizedOutputDirectory;

    /** @var non-empty-string */
    private string $verifiedCompiledFileName;

    /**
     * @param non-empty-string $outputDirectory
     * @param class-string     $containerClass  container class as fully qualified name
     */
    public function __construct(
        private readonly string $outputDirectory,
        private readonly string $containerClass,
        private readonly DiContainerDefinitionsInterface $diContainerDefinitions,
        private readonly DiDefinitionTransformerInterface $definitionTransform,
        private readonly InvalidBehaviorCompileEnum $invalidBehaviorCompile,
    ) {}

    public function getOutputDirectory(): string
    {
        if (!isset($this->normalizedOutputDirectory)) {
            $fixedDir = realpath($this->outputDirectory);

            if (false === $fixedDir) {
                throw new RuntimeException(
                    sprintf('Compiler output directory "%s" from parameter $outputDirectory is invalid.', $this->outputDirectory)
                );
            }

            if (!is_dir($fixedDir) || !is_readable($fixedDir) || !is_writable($fixedDir)) {
                throw new RuntimeException(
                    sprintf('Compiler output directory "%s" from parameter $outputDirectory must be exist and be readable and writable.', $fixedDir)
                );
            }

            $this->normalizedOutputDirectory = $fixedDir;
        }

        return $this->normalizedOutputDirectory;
    }

    public function getContainerFQN(): CompiledContainerFQN
    {
        if (isset($this->compiledContainerFQN)) {
            return $this->compiledContainerFQN;
        }

        $pos = strrpos($this->containerClass, '\\');

        /** @var class-string $class */
        $class = false === $pos ? $this->containerClass : substr($this->containerClass, $pos + 1);
        $namespace = false === $pos ? '' : ltrim(substr($this->containerClass, 0, $pos), '\\');

        return $this->compiledContainerFQN = new class($namespace, $class) implements CompiledContainerFQN {
            /** @var non-empty-string */
            private string $fqn;

            /** @param class-string $class */
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
                return $this->fqn ??= '' !== $this->namespace
                    ? '\\'.$this->namespace.'\\'.$this->class
                    : '\\'.$this->class;
            }
        };
    }

    public function compile(): string
    {
        $this->diContainerDefinitions->reset();

        $compiledClassFQN = $this->getContainerFQN()->getFQN();
        $containerEntry = new CompiledEntry(expression: '$this', returnType: $compiledClassFQN);

        $this->mapServiceMethodToContainerId = [
            'resolve_psr_container' => [ContainerInterface::class, $containerEntry],
            'resolve_di_container_interface' => [DiContainerInterface::class, $containerEntry],
            'resolve_di_container' => [DiContainer::class, $containerEntry],
            'resolve_compiled_container' => [$compiledClassFQN, $containerEntry],
        ];

        // Exclude container identifiers already compiled. See top.
        $this->diContainerDefinitions->excludeContainerIdentifier(
            ContainerInterface::class,
            DiContainerInterface::class,
            DiContainer::class,
            $compiledClassFQN,
        );

        $serviceSuffix = 0;

        /** @var null|non-empty-string $serviceMethodUnique */
        $serviceMethodUnique = null;

        foreach ($this->diContainerDefinitions->getDefinitions() as $id => $definition) {
            try {
                $compiledEntity = $this->definitionTransform
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
        }

        ob_start();

        require __DIR__.'/template.php';

        return (string) ob_get_clean();
    }

    public function compileToFile(): string
    {
        $file = $this->fileNameForCompiledContainer();

        if (file_exists($file)) {
            return $file;
        }

        $content = $this->compile();

        $resource = false !== ($resource = @tmpfile())
            ? $resource
            : throw $this->fileOperationException('Cannot create temporary file.');

        $tmpFileName = stream_get_meta_data($resource)['uri'] ?? 'unknown';

        if (false === @fwrite($resource, $content)) {
            throw $this->fileOperationException(
                sprintf('Cannot write to temporary file "%s".', $tmpFileName),
                $resource
            );
        }

        if (false === @rename($tmpFileName, $file)) {
            throw $this->fileOperationException(
                sprintf('Cannot rename file from "%s" to "%s".', $tmpFileName, $file),
                $resource
            );
        }

        @fclose($resource);

        return $file;
    }

    public function fileNameForCompiledContainer(): string
    {
        return $this->verifiedCompiledFileName ??= $this->getOutputDirectory().DIRECTORY_SEPARATOR.$this->getContainerFQN()->getClass().'.php';
    }

    /**
     * @param null|resource $r
     */
    private function fileOperationException(string $message, $r = null): RuntimeException
    {
        $internalMessage = isset(error_get_last()['message']) ? ' '.error_get_last()['message'] : '';

        if (null !== $r) {
            @fclose($r);
        }

        return new RuntimeException($message.$internalMessage);
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
