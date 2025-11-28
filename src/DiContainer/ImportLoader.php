<?php

declare(strict_types=1);

namespace Kaspi\DiContainer;

use InvalidArgumentException;
use Kaspi\DiContainer\Finder\FinderFile;
use Kaspi\DiContainer\Finder\FinderFullyQualifiedName;
use Kaspi\DiContainer\Interfaces\Finder\FinderFileInterface;
use Kaspi\DiContainer\Interfaces\Finder\FinderFullyQualifiedNameInterface;
use Kaspi\DiContainer\Interfaces\ImportLoaderInterface;

use function sprintf;

/**
 * @phpstan-import-type ItemFQN from FinderFullyQualifiedNameInterface
 */
final class ImportLoader implements ImportLoaderInterface
{
    public function __construct(
        private ?FinderFileInterface $finderFile = null,
        private ?FinderFullyQualifiedNameInterface $finderFullyQualifiedName = null
    ) {}

    public function __clone(): void
    {
        if (null !== $this->finderFile) {
            $this->finderFile = new FinderFile();
        }

        if (null !== $this->finderFullyQualifiedName) {
            $this->finderFullyQualifiedName = new FinderFullyQualifiedName();
        }
    }

    public function setSrc(string $src, array $excludeFilesRegExpPattern = [], array $availableExtensions = ['php']): static
    {
        if (null === $this->finderFile) {
            $this->finderFile = new FinderFile();
        }

        $this->finderFile->setSrc($src)
            ->setExcludeRegExpPattern($excludeFilesRegExpPattern)
            ->setAvailableExtensions($availableExtensions)
        ;

        return $this;
    }

    public function getSrc(): string
    {
        if (null === $this->finderFile) {
            throw new InvalidArgumentException(sprintf('Need set source directory. Use method %s::setSrc().', self::class));
        }

        return $this->finderFile->getSrc();
    }

    public function getFullyQualifiedName(string $namespace): iterable
    {
        if (null === $this->finderFile) {
            throw new InvalidArgumentException(sprintf('Need set source directory. Use method %s::setSrc().', self::class));
        }

        if (null === $this->finderFullyQualifiedName) {
            $this->finderFullyQualifiedName = new FinderFullyQualifiedName();
        }

        // @var ItemFQN $itemFQN
        yield from $this->finderFullyQualifiedName
            ->setNamespace($namespace)
            ->setFiles($this->finderFile->getFiles())
            ->find()
        ;
    }
}
