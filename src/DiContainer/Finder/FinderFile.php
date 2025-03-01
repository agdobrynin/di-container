<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Finder;

use Kaspi\DiContainer\Interfaces\Finder\FinderFileInterface;

final class FinderFile implements FinderFileInterface
{
    /**
     * @param non-empty-string       $src     source directory
     * @param list<non-empty-string> $exclude exclude pathnames matching a pattern
     */
    public function __construct(
        private string $src,
        private array $exclude = [],
        private ?string $extension = 'php',
    ) {
        $fixedSrc = \realpath($src);

        if (false === $fixedSrc) {
            throw new \InvalidArgumentException(
                \sprintf('Cannot get by "\realpath()" for argument $src. Got: "%s"', $src)
            );
        }

        if (!\is_dir($fixedSrc) || !\is_readable($fixedSrc)) {
            throw new \InvalidArgumentException(
                \sprintf('Argument $src must be readable directory. Got: "%s"', $fixedSrc)
            );
        }

        $this->src = $fixedSrc;
    }

    public function getFiles(): iterable
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $this->src,
                \FilesystemIterator::CURRENT_AS_FILEINFO | \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS
            )
        );

        foreach ($iterator as $entry) {
            /** @var \DirectoryIterator $entry */
            if ($entry->isFile()
                && \is_string($realPath = $entry->getRealPath())
                && (null === $this->extension || $this->extension === \strtolower($entry->getExtension()))
                && !$this->isExcluded($realPath)
            ) {
                yield $realPath; // @phpstan-ignore generator.keyType
            }
        }
    }

    private function isExcluded(string $fileRealPath): bool
    {
        if ([] === $this->exclude) {
            return false;
        }

        foreach ($this->exclude as $partOfRealPath) {
            // @todo How about regexp or glob expression?
            if (\str_contains($fileRealPath, $partOfRealPath)) {
                return true;
            }
        }

        return false;
    }
}
