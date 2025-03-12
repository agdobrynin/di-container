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
     * @var non-empty-string
     */
    private string $src;

    /**
     * @var list<non-empty-string>
     */
    private array $excludeRegExpPattern = [];

    /**
     * @var list<non-empty-string>
     */
    private array $extensions = ['php'];

    public function __construct() {}

    public function setSrc(string $src): static
    {
        $fixedSrc = realpath($src);

        if (false === $fixedSrc) {
            throw new InvalidArgumentException(
                sprintf('Cannot get by "\realpath()" for argument $src. Got: "%s".', $src)
            );
        }

        if (!is_dir($fixedSrc) || !is_readable($fixedSrc)) {
            throw new InvalidArgumentException(
                sprintf('Argument $src must be readable directory. Got: "%s".', $fixedSrc)
            );
        }

        $this->src = $fixedSrc;

        return $this;
    }

    public function setExcludeRegExpPattern(array $excludeRegExpPattern): static
    {
        $this->excludeRegExpPattern = $excludeRegExpPattern;

        return $this;
    }

    public function setAvailableExtensions(array $extensions): static
    {
        $this->extensions = array_map('\strtolower', $extensions); // @phpstan-ignore assign.propertyType

        return $this;
    }

    public function getFiles(): Iterator
    {
        if (!isset($this->src)) {
            throw new InvalidArgumentException('Need set source directory. Use method FinderFile::setSrc().');
        }

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
