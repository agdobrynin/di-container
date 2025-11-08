<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition;

use Generator;
use Kaspi\DiContainer\Exception\AutowireException;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionAutowireInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionInvokableInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionNoArgumentsInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionTaggedAsInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiTaggedDefinitionInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerNeedSetExceptionInterface;
use Kaspi\DiContainer\LazyDefinitionIterator;
use Kaspi\DiContainer\Traits\DiAutowireTrait;
use Kaspi\DiContainer\Traits\DiContainerTrait;
use SplPriorityQueue;

use function array_map;
use function explode;
use function get_debug_type;
use function in_array;
use function interface_exists;
use function is_string;
use function sprintf;
use function str_starts_with;
use function trim;
use function var_export;

final class DiDefinitionTaggedAs implements DiDefinitionTaggedAsInterface, DiDefinitionNoArgumentsInterface
{
    use DiContainerTrait;
    use DiAutowireTrait;

    private bool $tagIsInterface;
    private string $keyOptimized;
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
        private string $tag,
        private bool $isLazy = true,
        private ?string $priorityDefaultMethod = null,
        bool $useKeys = true,
        private ?string $key = null,
        private ?string $keyDefaultMethod = null,
        private array $containerIdExclude = [],
        private bool $selfExclude = true,
    ) {
        $this->isUseKeysComputed = $useKeys || null !== $key || null !== $keyDefaultMethod;
    }

    public function getServicesTaggedAs(): iterable
    {
        return $this->isLazy
            ? new LazyDefinitionIterator($this->getContainer(), $this->getContainerIdentifiers())
            : array_map(fn (string $id) => $this->getContainer()->get($id), $this->getContainerIdentifiers());
    }

    public function getDefinition(): string
    {
        return $this->tag;
    }

    public function getCallingByService(): ?DiDefinitionAutowireInterface
    {
        return $this->callingByDefinitionAutowire;
    }

    public function setCallingByService(?DiDefinitionAutowireInterface $definitionAutowire = null): static
    {
        $this->callingByDefinitionAutowire = $definitionAutowire;

        return $this;
    }

    /**
     * @return array<non-empty-string, non-empty-string>
     */
    private function getContainerIdentifiersWithKey(): array
    {
        $mapKeyCollectionToContainerIdentifier = [];

        foreach ($this->getContainerIdentifiersOfTaggedServiceByTag() as [$containerIdentifier, $definition]) {
            $keyCollection = $this->getKeyFromTagOptionsOrFromKeyDefaultMethod($containerIdentifier, $definition);

            if (!isset($mapKeyCollectionToContainerIdentifier[$keyCollection])) {
                $mapKeyCollectionToContainerIdentifier[$keyCollection] = $containerIdentifier;
            }
        }

        return $mapKeyCollectionToContainerIdentifier;
    }

    /**
     * @return list<non-empty-string>
     */
    private function getContainerIdentifiersWithoutKey(): array
    {
        $containerIdentifiers = [];

        foreach ($this->getContainerIdentifiersOfTaggedServiceByTag() as [$containerIdentifier]) {
            $containerIdentifiers[] = $containerIdentifier;
        }

        return $containerIdentifiers;
    }

    /**
     * @return array<non-empty-string, non-empty-string>|list<non-empty-string>
     */
    private function getContainerIdentifiers(): array
    {
        return $this->isUseKeysComputed ? $this->getContainerIdentifiersWithKey() : $this->getContainerIdentifiersWithoutKey();
    }

    /**
     * @return Generator<array{0: non-empty-string, 1: DiDefinitionAutowireInterface|DiTaggedDefinitionInterface}>
     *
     * @throws ContainerNeedSetExceptionInterface
     */
    private function getContainerIdentifiersOfTaggedServiceByTag(): Generator
    {
        $this->tagIsInterface ??= interface_exists($this->tag);
        $taggedServices = new SplPriorityQueue();
        $taggedServices->setExtractFlags(SplPriorityQueue::EXTR_DATA);

        /** @var non-empty-string $containerIdentifier */
        foreach ($this->getContainer()->getDefinitions() as $containerIdentifier => $definition) {
            if (false === ($definition instanceof DiTaggedDefinitionInterface)
                || in_array($containerIdentifier, $this->containerIdExclude, true)
                || ($this->selfExclude && $containerIdentifier === $this->getCallingByService()?->getDefinition()->getName())) {
                continue;
            }

            if ($definition instanceof DiDefinitionInvokableInterface) {
                $definition->setContainer($this->getContainer());
            }

            if (
                (
                    !$this->tagIsInterface && $definition->hasTag($this->tag)
                )
                || (
                    $this->tagIsInterface
                        && $definition instanceof DiDefinitionAutowireInterface
                        && $definition->getDefinition()->implementsInterface($this->tag)
                )
            ) {
                $operationOptions = [];

                if ($definition instanceof DiDefinitionAutowireInterface) {
                    $operationOptions['priority.default_method'] = $this->priorityDefaultMethod;
                }

                // 🚩 Tag with higher priority early in list.
                $taggedServices->insert([$containerIdentifier, $definition], $definition->geTagPriority($this->tag, $operationOptions));
            }
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
     */
    private function getKeyFromTagOptionsOrFromKeyDefaultMethod(string $identifier, DiDefinitionAutowireInterface|DiTaggedDefinitionInterface $taggedAs): string
    {
        if (null !== $this->key) {
            $this->keyOptimized ??= '' !== trim($this->key)
                ? $this->key
                : throw new AutowireException('Argument $key must be non-empty string.');

            $optionKey = $taggedAs->getTag($this->tag)[$this->keyOptimized] ?? null;

            if (null !== $optionKey) {
                if (!is_string($optionKey) || '' === trim($optionKey)) {
                    throw new AutowireException(
                        sprintf(
                            'Tag option "%s" for container identifier "%s" with tag "%s" has an error: the value must be non-empty string. Got: type "%s", value: %s.',
                            $this->keyOptimized,
                            $identifier,
                            $this->tag,
                            get_debug_type($optionKey),
                            var_export($optionKey, true)
                        )
                    );
                }

                if (!$taggedAs instanceof DiDefinitionAutowireInterface) {
                    return $optionKey;
                }

                if (!str_starts_with($optionKey, 'self::')) {
                    return $optionKey;
                }

                $method = explode('::', $optionKey)[1];
                $howGetOptions = sprintf('Get key by "%s::%s()" for tag "%s" has an error:', $taggedAs->getIdentifier(), $method, $this->tag);

                /** @var string $key */
                $key = self::callStaticMethod($taggedAs, $method, true, $howGetOptions, ['string'], $this->tag, $taggedAs->getTag($this->tag) ?? []);

                return '' === $key || '' === trim($key)
                    ? throw new AutowireException(sprintf('%s return value must be non-empty string. Got value: "%s"', $howGetOptions, $key))
                    : $key;
            }
        }

        if (null === $this->keyDefaultMethod || !($taggedAs instanceof DiDefinitionAutowireInterface)) {
            return $identifier;
        }

        $howGetKeyOption = sprintf('Get default key by "%s::%s()" for tag "%s" has an error:', $taggedAs->getIdentifier(), $this->keyDefaultMethod, $this->tag);
        $key = self::callStaticMethod($taggedAs, $this->keyDefaultMethod, false, $howGetKeyOption, ['string'], $this->tag, $taggedAs->getTag($this->tag) ?? []);

        // @phpstan-ignore return.type
        return null === $key || '' === $key
            ? $identifier
            : $key;
    }
}
