<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Finder;

use DirectoryIterator;
use FilesystemIterator;
use Generator;
use InvalidArgumentException;
use Iterator;
use Kaspi\DiContainer\Interfaces\Finder\FinderFileInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

use function array_map;
use function fnmatch;
use function in_array;
use function is_dir;
use function is_readable;
use function realpath;
use function sprintf;
use function strtolower;

final class FinderFile implements FinderFileInterface
{
    /** @var non-empty-string */
    private string $normalizedSrc;

    /** @var list<non-empty-string> */
    private array $normalizedAvailableExtensions;

    private RecursiveIteratorIterator $recursiveDirectoryIterator;

    /**
     * Note: parameter `$exclude` use php function `\fnmatch()`, detail info about file pattern see in documentation.
     *
     * @see https://www.php.net/manual/en/function.fnmatch.php
     *
     * @param non-empty-string       $src                 source directory
     * @param list<non-empty-string> $exclude             patterns that exclude files
     * @param list<non-empty-string> $availableExtensions available file extensions
     */
    public function __construct(private readonly string $src, private readonly array $exclude = [], private readonly array $availableExtensions = ['php']) {}

    public function getSrc(): string
    {
        return $this->src;
    }

    public function getExclude(): array
    {
        return $this->exclude;
    }

    public function getAvailableExtensions(): array
    {
        return $this->availableExtensions;
    }

    public function getMatchedFiles(): Iterator
    {
        foreach ($this->fetchFiles() as $realPath => $entry) {
            if (!$this->isExcluded($realPath)) {
                yield $entry;
            }
        }
    }

    public function getExcludedFiles(): Iterator
    {
        foreach ($this->fetchFiles() as $realPath => $entry) {
            if ($this->isExcluded($realPath)) {
                yield $entry;
            }
        }
    }

    /**
     * @return Generator<non-empty-string, DirectoryIterator>
     */
    private function fetchFiles(): Generator
    {
        if (!isset($this->normalizedSrc)) {
            $fixedSrc = realpath($this->src);

            if (false === $fixedSrc) {
                throw new InvalidArgumentException(
                    sprintf('Source directory "%s" from parameter $src is invalid.', $this->src)
                );
            }

            if (!is_dir($fixedSrc) || !is_readable($fixedSrc)) {
                throw new InvalidArgumentException(
                    sprintf('Source directory "%s" from parameter $src must be readable.', $fixedSrc)
                );
            }

            $this->normalizedSrc = $fixedSrc;
        }

        $this->recursiveDirectoryIterator ??= new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $this->normalizedSrc,
                FilesystemIterator::CURRENT_AS_FILEINFO | FilesystemIterator::SKIP_DOTS | FilesystemIterator::UNIX_PATHS
            )
        );

        $this->normalizedAvailableExtensions ??= array_map(strtolower(...), $this->availableExtensions);

        foreach ($this->recursiveDirectoryIterator as $entry) {
            /** @var DirectoryIterator $entry */
            if (($realPath = $entry->getRealPath())
                && $entry->isFile()
                && (
                    [] === $this->normalizedAvailableExtensions
                    || in_array(strtolower($entry->getExtension()), $this->normalizedAvailableExtensions, true)
                )
            ) {
                yield $realPath => $entry;
            }
        }
    }

    private function isExcluded(string $fileRealPath): bool
    {
        if ([] === $this->exclude) {
            return false;
        }

        foreach ($this->exclude as $partPattern) {
            if (fnmatch($partPattern, $fileRealPath)) {
                return true;
            }
        }

        return false;
    }
}
