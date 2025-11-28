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
    /** @var non-empty-string */
    private string $verifiedSrc;

    /** @var list<non-empty-string> */
    private array $normalizedAvailableExtensions;

    /**
     * @param non-empty-string       $src
     * @param list<non-empty-string> $excludeRegExpPattern
     * @param list<non-empty-string> $availableExtensions
     */
    public function __construct(private readonly string $src, private readonly array $excludeRegExpPattern = [], private readonly array $availableExtensions = ['php']) {}

    public function getSrc(): string
    {
        return $this->src;
    }

    public function getExcludeRegExpPattern(): array
    {
        return $this->excludeRegExpPattern;
    }

    public function getAvailableExtensions(): array
    {
        return $this->availableExtensions;
    }

    public function getFiles(): Iterator
    {
        if (!isset($this->verifiedSrc)) {
            $fixedSrc = realpath($this->src);

            if (false === $fixedSrc) {
                throw new InvalidArgumentException(
                    sprintf('Cannot resolve source directory by "\realpath()" from parameter $src. Got: "%s".', $this->src)
                );
            }

            if (!is_dir($fixedSrc) || !is_readable($fixedSrc)) {
                throw new InvalidArgumentException(
                    sprintf('Source directory from parameter $src must be readable. Got: "%s".', $fixedSrc)
                );
            }

            $this->verifiedSrc = $fixedSrc;
        }

        $this->normalizedAvailableExtensions ??= array_map(strtolower(...), $this->availableExtensions);

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $this->verifiedSrc,
                FilesystemIterator::CURRENT_AS_FILEINFO | FilesystemIterator::SKIP_DOTS | FilesystemIterator::UNIX_PATHS
            )
        );

        foreach ($iterator as $entry) {
            /** @var DirectoryIterator $entry */
            if (($realPath = $entry->getRealPath())
                && !$this->isExcluded($realPath)
                && $entry->isFile()
                && ([] === $this->normalizedAvailableExtensions || in_array(strtolower($entry->getExtension()), $this->normalizedAvailableExtensions, true))
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
