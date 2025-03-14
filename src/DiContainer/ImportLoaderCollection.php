<?php

declare(strict_types=1);

namespace Kaspi\DiContainer;

use InvalidArgumentException;
use Kaspi\DiContainer\Interfaces\ImportLoaderCollectionInterface;
use Kaspi\DiContainer\Interfaces\ImportLoaderInterface;

use function sprintf;

final class ImportLoaderCollection implements ImportLoaderCollectionInterface
{
    private int $cloneCounterImportLoader = 0;

    /**
     * @var array<non-empty-string, ImportLoaderInterface>
     */
    private array $import = [];

    public function __construct(private ?ImportLoaderInterface $importLoader = null) {}

    public function __clone(): void
    {
        $this->import = [];
        $this->cloneCounterImportLoader = 0;

        if (null !== $this->importLoader) {
            $this->importLoader = clone $this->importLoader;
        }
    }

    public function importFromNamespace(string $namespace, string $src, array $excludeFilesRegExpPattern = [], array $availableExtensions = ['php']): static
    {
        if (isset($this->import[$namespace])) {
            throw new InvalidArgumentException(
                sprintf('Namespace "%s" is already imported.', $namespace)
            );
        }

        if (null === $this->importLoader) {
            $currentImportLoader = new ImportLoader();
        } elseif ($this->cloneCounterImportLoader > 0) {
            $currentImportLoader = clone $this->importLoader;
        } else {
            ++$this->cloneCounterImportLoader;
            $currentImportLoader = $this->importLoader;
        }

        $this->import[$namespace] = $currentImportLoader->setSrc($src, $excludeFilesRegExpPattern, $availableExtensions);

        return $this;
    }

    public function getFullyQualifiedName(): iterable
    {
        $key = 0;

        foreach ($this->import as $namespace => $importLoader) {
            foreach ($importLoader->getFullyQualifiedName($namespace) as $item) {
                yield $key++ => ['namespace' => $namespace, 'itemFQN' => $item];
            }
        }
    }
}
