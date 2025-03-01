<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Finder;

use Kaspi\DiContainer\Interfaces\Finder\FinderFileInterface;

final class FinderFile implements FinderFileInterface
{
    /**
     * @param non-empty-string       $src                  source directory
     * @param list<non-empty-string> $excludeRegExpPattern exclude matching by regexp pattern
     */
    public function __construct(
        private string $src,
        private array $excludeRegExpPattern = [],
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
            if (($realPath = $entry->getRealPath())
                && !$this->isExcluded($realPath)
                && $entry->isFile()
                && (null === $this->extension || $this->extension === \strtolower($entry->getExtension()))
            ) {
                yield $entry; // @phpstan-ignore generator.keyType
            }
        }
    }

    private function isExcluded(string $fileRealPath): bool
    {
        if ([] === $this->excludeRegExpPattern) {
            return false;
        }

        foreach ($this->excludeRegExpPattern as $partOfPregPattern) {
            if (1 === \preg_match($partOfPregPattern, $fileRealPath)) {
                return true;
            }
        }

        return false;
    }
}
