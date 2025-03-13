<?php

declare(strict_types=1);

namespace Kaspi\DiContainer;

use Kaspi\DiContainer\Finder\FinderFile;
use Kaspi\DiContainer\Finder\FinderFullyQualifiedName;
use Kaspi\DiContainer\Interfaces\Finder\FinderFileInterface;
use Kaspi\DiContainer\Interfaces\Finder\FinderFullyQualifiedNameInterface;
use Kaspi\DiContainer\Interfaces\ImportLoaderInterface;

/**
 * @phpstan-import-type ItemFQN from FinderFullyQualifiedNameInterface
 */
final class ImportLoader implements ImportLoaderInterface
{
    private FinderFileInterface $finderFile;

    private FinderFullyQualifiedNameInterface $finderFullyQualifiedName;

    public function __construct(
        ?FinderFileInterface $finderFile = null,
        ?FinderFullyQualifiedNameInterface $finderFullyQualifiedName = null
    ) {
        $this->finderFile = $finderFile ?? new FinderFile();
        $this->finderFullyQualifiedName = $finderFullyQualifiedName ?? new FinderFullyQualifiedName();
    }

    public function __clone(): void
    {
        $this->finderFile = new FinderFile();
        $this->finderFullyQualifiedName = new FinderFullyQualifiedName();
    }

    public function setSrc(string $src, array $excludeFilesRegExpPattern = [], array $availableExtensions = ['php']): static
    {
        $this->finderFile->setSrc($src)
            ->setExcludeRegExpPattern($excludeFilesRegExpPattern)
            ->setAvailableExtensions($availableExtensions)
        ;

        return $this;
    }

    public function getFullyQualifiedName(string $namespace): iterable
    {
        // @var ItemFQN $itemFQN
        yield from $this->finderFullyQualifiedName
            ->setNamespace($namespace)
            ->setFiles($this->finderFile->getFiles())
            ->find()
        ;
    }
}
