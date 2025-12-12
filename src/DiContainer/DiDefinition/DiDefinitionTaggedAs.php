<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition;

use Generator;
use InvalidArgumentException;
use Kaspi\DiContainer\Compiler\CompiledEntry;
use Kaspi\DiContainer\Exception\AutowireException;
use Kaspi\DiContainer\Exception\DiDefinitionCompileException;
use Kaspi\DiContainer\Exception\DiDefinitionException;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\CompiledEntryInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionAutowireInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionCompileInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionNoArgumentsInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionTaggedAsInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiTaggedDefinitionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
use Kaspi\DiContainer\LazyDefinitionIterator;
use SplPriorityQueue;

use function array_map;
use function explode;
use function in_array;
use function is_callable;
use function is_string;
use function sprintf;
use function str_starts_with;
use function trim;
use function var_export;

use const PHP_EOL;

final class DiDefinitionTaggedAs implements DiDefinitionTaggedAsInterface, DiDefinitionNoArgumentsInterface, DiDefinitionCompileInterface
{
    private bool $keyChecked;
    private bool $isUseKeysComputed;

    private ?DiDefinitionAutowireInterface $callingByDefinitionAutowire = null;

    /**
     * @param non-empty-string       $tag
     * @param null|non-empty-string  $priorityDefaultMethod priority from class::method()
     * @param null|non-empty-string  $key                   identifier of tagged definition from tag options (meta-data)
     * @param null|non-empty-string  $keyDefaultMethod      if $key not found in tag options - try get it from class::method()
     * @param list<non-empty-string> $containerIdExclude    exclude container identifiers from collection
     * @param bool                   $selfExclude           exclude the php calling class from the collection
     */
    public function __construct(
        private readonly string $tag,
        private readonly bool $isLazy = true,
        private readonly ?string $priorityDefaultMethod = null,
        bool $useKeys = true,
        private readonly ?string $key = null,
        private readonly ?string $keyDefaultMethod = null,
        private readonly array $containerIdExclude = [],
        private readonly bool $selfExclude = true,
    ) {
        $this->isUseKeysComputed = $useKeys || null !== $key || null !== $keyDefaultMethod;
    }

    public function resolve(DiContainerInterface $container, mixed $context = null): iterable
    {
        return $this->isLazy
            ? new LazyDefinitionIterator($container, $this->exposeContainerIdentifiers($container, $context))
            : array_map(static fn (string $id) => $container->get($id), $this->exposeContainerIdentifiers($container, $context));
    }

    public function compile(string $containerVariableName, DiContainerInterface $container, ?string $scopeServiceVariableName = null, array $scopeVariableNames = [], mixed $context = null): CompiledEntryInterface
    {
        try {
            $mapContainerIdentifiers = $this->exposeContainerIdentifiers($container, $context);
        } catch (DiDefinitionExceptionInterface $e) {
            throw new DiDefinitionCompileException(
                sprintf('Cannot compile tagged services. Tag "%s".', $this->tag),
                previous: $e
            );
        }

        if ($this->isLazy) {
            // build map tagged key => container identifier
            $ids = '['.PHP_EOL;

            foreach ($mapContainerIdentifiers as $key => $containerIdentifier) {
                $ids .= sprintf('  %s => %s,'.PHP_EOL, var_export($key, true), var_export($containerIdentifier, true));
            }

            $ids .= ']'.PHP_EOL;
            $expression = sprintf('new \Kaspi\DiContainer\LazyDefinitionIterator(%s, %s)', $containerVariableName, $ids);

            $comment = sprintf('// Lazy load services for tag %s', var_export($this->tag, true));

            return new CompiledEntry($expression, $comment, [], false, '\Kaspi\DiContainer\LazyDefinitionIterator');
        }

        $expression = '[';

        foreach ($mapContainerIdentifiers as $key => $containerIdentifier) {
            $expression .= sprintf('  %s => %s->get(%s),'.PHP_EOL, var_export($key, true), $containerVariableName, var_export($containerIdentifier, true));
        }

        $expression .= ']';

        $comment = sprintf('// Services for tag %s', var_export($this->tag, true));

        return new CompiledEntry($expression, $comment, [], false, 'array');
    }

    public function getDefinition(): string
    {
        return $this->tag;
    }

    /**
     * @return array<non-empty-string|non-negative-int, non-empty-string>
     *
     * @throws DiDefinitionExceptionInterface
     */
    public function exposeContainerIdentifiers(DiContainerInterface $container, mixed $context = null): iterable
    {
        if ($context instanceof DiDefinitionAutowireInterface) {
            $this->callingByDefinitionAutowire = $context;
        }

        $mapKeyToContainerIdentifier = [];

        foreach ($this->filterByExcludeSortDefinitionByPriority($container->findTaggedDefinitions($this->tag)) as [$containerIdentifier, $definition]) {
            if ($this->isUseKeysComputed) {
                $keyCollection = $this->getTagKeyFromTagOptionsOrFromClassMethod($containerIdentifier, $definition);
                if (!isset($mapKeyToContainerIdentifier[$keyCollection])) {
                    $mapKeyToContainerIdentifier[$keyCollection] = $containerIdentifier;
                }
            } else {
                $mapKeyToContainerIdentifier[] = $containerIdentifier;
            }
        }

        return $mapKeyToContainerIdentifier;
    }

    /**
     * @param iterable<non-empty-string, DiDefinitionAutowireInterface|DiTaggedDefinitionInterface> $definitions
     *
     * @return Generator<array{0: non-empty-string, 1: DiDefinitionAutowireInterface|DiTaggedDefinitionInterface}>
     *
     * @throws DiDefinitionExceptionInterface
     */
    private function filterByExcludeSortDefinitionByPriority(iterable $definitions): Generator
    {
        if ($definitions instanceof Generator && !$definitions->valid()) {
            return;
        }

        $taggedServices = new SplPriorityQueue();
        $taggedServices->setExtractFlags(SplPriorityQueue::EXTR_DATA);

        foreach ($definitions as $containerIdentifier => $definition) {
            if (in_array($containerIdentifier, $this->containerIdExclude, true)
                || ($this->selfExclude && $containerIdentifier === $this->callingByDefinitionAutowire?->getDefinition()->getName())) {
                continue;
            }

            $operationOptions = [];

            if ($definition instanceof DiDefinitionAutowireInterface) {
                $operationOptions['priority.default_method'] = $this->priorityDefaultMethod;
            }

            // 🚩 Tag with higher priority early in list.
            $taggedServices->insert([$containerIdentifier, $definition], $definition->geTagPriority($this->tag, $operationOptions));
        }

        /** @var array{0: non-empty-string, 1: DiDefinitionAutowireInterface|DiTaggedDefinitionInterface} $item */
        foreach ($taggedServices as $item) {
            yield $item;
        }
    }

    /**
     * @param non-empty-string $identifier
     *
     * @return non-empty-string
     *
     * @throws DiDefinitionExceptionInterface
     */
    private function getTagKeyFromTagOptionsOrFromClassMethod(string $identifier, DiDefinitionAutowireInterface|DiTaggedDefinitionInterface $taggedAs): string
    {
        if (null !== $this->key) {
            if (!isset($this->keyChecked)) {
                if ('' === trim($this->key)) {
                    throw new DiDefinitionException(
                        sprintf('Parameter $key for %s::__construct() must be non-empty string. Tag is "%s".', self::class, $this->tag)
                    );
                }

                $this->keyChecked = true;
            }

            $optionKey = $taggedAs->getTag($this->tag)[$this->key] ?? null;

            if (null !== $optionKey) {
                if (!is_string($optionKey) || '' === trim($optionKey)) {
                    throw new DiDefinitionException(
                        sprintf('Cannot get key for tag "%s" via tag options. The value of option name "%s" must be non-empty string. Got value: %s', $this->tag, $this->key, var_export($optionKey, true))
                    );
                }

                if (!$taggedAs instanceof DiDefinitionAutowireInterface) {
                    return $optionKey;
                }

                if (!str_starts_with($optionKey, 'self::')) {
                    return $optionKey;
                }

                try {
                    $method = explode('::', $optionKey)[1];

                    return $this->getTagKeyFromClassMethod($taggedAs->getIdentifier(), $method, $taggedAs);
                } catch (AutowireException|InvalidArgumentException $e) {
                    throw new DiDefinitionException(
                        message: sprintf('Cannot get key for tag "%s" via method %s::%s(). Caused by: %s', $this->tag, $taggedAs->getIdentifier(), $method, $e->getMessage()),
                        previous: $e
                    );
                }
            }
        }

        if (null === $this->keyDefaultMethod || !($taggedAs instanceof DiDefinitionAutowireInterface)) {
            return $identifier;
        }

        try {
            return $this->getTagKeyFromClassMethod($taggedAs->getIdentifier(), $this->keyDefaultMethod, $taggedAs);
        } catch (InvalidArgumentException) {
            return $identifier;
        } catch (AutowireException $e) {
            throw new DiDefinitionException(
                message: sprintf('Cannot get key for tag "%s" via default method %s::%s(). Caused by: %s', $this->tag, $taggedAs->getIdentifier(), $this->keyDefaultMethod, $e->getMessage()),
                previous: $e
            );
        }
    }

    /**
     * @return non-empty-string
     *
     * @throws AutowireException|InvalidArgumentException
     */
    private function getTagKeyFromClassMethod(string $class, string $method, DiTaggedDefinitionInterface $taggedAs): string
    {
        $callable = [$class, $method];

        if (!is_callable($callable)) {
            throw new InvalidArgumentException('Method must be exist and declared with public and static modifiers.');
        }

        $key = $callable($this->tag, $taggedAs->getTag($this->tag) ?? []);

        return is_string($key) && '' !== $key
            ? $key
            : throw new AutowireException(sprintf('Method must return non-empty string but return "%s"', var_export($key, true)));
    }
}
