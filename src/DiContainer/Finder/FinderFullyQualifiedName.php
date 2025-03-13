<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Finder;

use Generator;
use InvalidArgumentException;
use Iterator;
use Kaspi\DiContainer\Interfaces\Finder\FinderFullyQualifiedNameInterface;
use ParseError;
use RuntimeException;
use SplFileInfo;

use function count;
use function in_array;
use function is_array;
use function preg_match;
use function sprintf;
use function str_ends_with;
use function str_starts_with;
use function token_get_all;

use const T_ABSTRACT;
use const T_CLASS;
use const T_INTERFACE;
use const T_NAME_FULLY_QUALIFIED;
use const T_NAME_QUALIFIED;
use const T_NAMESPACE;
use const T_STRING;
use const T_TRAIT;
use const TOKEN_PARSE;

/**
 * @phpstan-import-type ItemFQN from FinderFullyQualifiedNameInterface
 */
final class FinderFullyQualifiedName implements FinderFullyQualifiedNameInterface
{
    /**
     * PSR-4 namespace prefix.
     *
     * @var non-empty-string
     */
    private string $namespace;

    /**
     * Files for parsing.
     *
     * @var iterable<non-negative-int, SplFileInfo>
     */
    private iterable $files;

    public function __construct() {}

    public function setNamespace(string $namespace): static
    {
        if (!str_ends_with($namespace, '\\')) {
            throw new InvalidArgumentException(
                sprintf('Argument $namespace must be end with symbol "\". Got: "%s".', $namespace)
            );
        }

        // @see https://www.php.net/manual/en/language.variables.basics.php
        if (1 !== preg_match('/^(?:[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*+\\\)++$/', $namespace)) {
            throw new InvalidArgumentException(
                sprintf('Argument $namespace must be compatible with PSR-4. Got "%s".', $namespace)
            );
        }

        $this->namespace = $namespace;

        return $this;
    }

    public function setFiles(iterable $files): static
    {
        $this->files = $files;

        return $this;
    }

    public function find(): Iterator
    {
        if (!isset($this->namespace)) {
            throw new InvalidArgumentException('Need set "namespace". Use method FinderFile::setNamespace().');
        }

        if (!isset($this->files)) {
            throw new InvalidArgumentException('Need set files for parsing. Use method FinderFile::setFiles().');
        }

        $key = 0;

        foreach ($this->files as $file) {
            yield from $this->findInFile($file, $key);
        }
    }

    /**
     * @param non-negative-int $key
     *
     * @return Generator<non-negative-int, ItemFQN>
     *
     * @throws RuntimeException
     */
    private function findInFile(SplFileInfo $file, int &$key): Generator
    {
        $f = $file->openFile('rb');
        $code = '';

        while (!$f->eof()) {
            $code .= $f->fread(8192);
        }

        try {
            $tokens = token_get_all($code, TOKEN_PARSE);
        } catch (ParseError $exception) {
            throw new RuntimeException(
                sprintf('Cannot parse code from file "%s". Reason: %s', $file, $exception->getMessage())
            );
        }

        $namespace = '';
        $isValidFqn = true;
        $level = 0;
        $fqnLevel = null;

        for ($i = 0, $totalTokens = count($tokens); $i < $totalTokens; ++$i) {
            [$token_id, $token_text] = is_array($tokens[$i])
                ? [$tokens[$i][0], $tokens[$i][1]]
                : [0, $tokens[$i]];

            if ('{' === $token_text) {
                ++$level;

                continue;
            }

            if ('}' === $token_text) {
                --$level;

                if ($level === $fqnLevel) {
                    $isValidFqn = true;
                }

                continue;
            }

            if (null !== $fqnLevel && $fqnLevel < $level) {
                continue;
            }

            if (T_NAMESPACE === $token_id) {
                for (++$i; $i < $totalTokens; ++$i) {
                    [$token_id, $token_text] = is_array($tokens[$i])
                        ? [$tokens[$i][0], $tokens[$i][1]]
                        : [0, $tokens[$i]];

                    if (in_array($token_id, [T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED], true)) {
                        $namespace = $token_text;

                        break;
                    }
                }

                continue;
            }

            if (in_array($token_id, [T_ABSTRACT, T_TRAIT], true)) {
                $isValidFqn = false;

                continue;
            }

            if ($isValidFqn && in_array($token_id, [T_CLASS, T_INTERFACE], true)) {
                $fqnItem = [];
                $classOrInterfaceTokenId = $token_id;

                for (++$i; $i < $totalTokens; ++$i) {
                    [$token_id, $token_text, $token_line] = is_array($tokens[$i])
                        ? [$tokens[$i][0], $tokens[$i][1], $tokens[$i][2]]
                        : [0, $tokens[$i], null];

                    if (T_STRING === $token_id
                        && [] === $fqnItem
                        && str_starts_with($fqn = $namespace.'\\'.$token_text, $this->namespace)) {
                        /** @var class-string $fqn */
                        $fqnItem = [
                            'fqn' => $fqn,
                            'tokenId' => $classOrInterfaceTokenId,
                            'line' => $token_line,
                            'file' => $file->getRealPath(),
                        ];
                    }

                    if ('{' === $token_text && [] !== $fqnItem) {
                        $fqnLevel = $level;
                        ++$level;

                        yield $key++ => $fqnItem;

                        break;
                    }
                }
            }
        }
    }
}
