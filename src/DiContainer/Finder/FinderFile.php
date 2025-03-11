<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Finder;

use DirectoryIterator;
use FilesystemIterator;
use InvalidArgumentException;
use Iterator;
use Kaspi\DiContainer\Interfaces\Finder\FinderFileInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

use function array_map;
use function in_array;
use function is_dir;
use function is_readable;
use function preg_match;
use function realpath;
use function sprintf;
use function strtolower;

final class FinderFile implements FinderFileInterface
{
    /**
     * @param non-empty-string       $src                  source directory
     * @param list<non-empty-string> $excludeRegExpPattern exclude matching by regexp pattern
     * @param list<non-empty-string> $extensions           files extensions, empty list available all files
     */
    public function __construct(
        private string $src,
        private array $excludeRegExpPattern = [],
        private array $extensions = ['php'],
    ) {
        $fixedSrc = realpath($src);

        if (false === $fixedSrc) {
            throw new InvalidArgumentException(
                sprintf('Cannot get by "\realpath()" for argument $src. Got: "%s"', $src)
            );
        }

        if (!is_dir($fixedSrc) || !is_readable($fixedSrc)) {
            throw new InvalidArgumentException(
                sprintf('Argument $src must be readable directory. Got: "%s"', $fixedSrc)
            );
        }

        $this->extensions = array_map('\strtolower', $extensions); // @phpstan-ignore assign.propertyType
        $this->src = $fixedSrc;
    }

    public function getFiles(): Iterator
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $this->src,
                FilesystemIterator::CURRENT_AS_FILEINFO | FilesystemIterator::SKIP_DOTS | FilesystemIterator::UNIX_PATHS
            )
        );

        foreach ($iterator as $entry) {
            /** @var DirectoryIterator $entry */
            if (($realPath = $entry->getRealPath())
                && !$this->isExcluded($realPath)
                && $entry->isFile()
                && ([] === $this->extensions || in_array(strtolower($entry->getExtension()), $this->extensions, true))
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
            if (1 === preg_match($partOfPregPattern, $fileRealPath)) {
                return true;
            }
        }

        return false;
    }
}
