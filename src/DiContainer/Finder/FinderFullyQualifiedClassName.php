<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Finder;

use Kaspi\DiContainer\Interfaces\Finder\FinderFullyQualifiedClassNameInterface;

final class FinderFullyQualifiedClassName implements FinderFullyQualifiedClassNameInterface
{
    /**
     * @param non-empty-string                         $namespace PSR-4 namespace prefix
     * @param iterable<non-negative-int, \SplFileInfo> $files     files for parsing
     */
    public function __construct(
        private string $namespace,
        private iterable $files,
    ) {
        if (!\str_ends_with($namespace, '\\')) {
            throw new \InvalidArgumentException(
                \sprintf('Argument $namespace must be end with symbol "\". Got: "%s"', $namespace)
            );
        }

        // @see https://www.php.net/manual/en/language.variables.basics.php
        if (1 !== \preg_match('/^(?:[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*+\\\)++$/', $namespace)) {
            throw new \InvalidArgumentException(
                \sprintf('Argument $namespace must be compatible with PSR-4. Got "%s".', $namespace)
            );
        }
    }

    public function find(): \Iterator
    {
        $key = 0;

        foreach ($this->files as $file) {
            yield from $this->findInFile($file, $key);
        }
    }

    /**
     * @param non-negative-int $key
     *
     * @return \Generator<non-negative-int, class-string>
     *
     * @throws \RuntimeException
     */
    private function findInFile(\SplFileInfo $file, int &$key): \Generator
    {
        $f = $file->openFile('rb');
        $code = '';

        while (!$f->eof()) {
            $code .= $f->fread(8192);
        }

        try {
            $tokens = \PhpToken::tokenize($code, \TOKEN_PARSE);
        } catch (\ParseError $exception) {
            throw new \RuntimeException(
                \sprintf('Cannot parse code in file "%s". Reason: %s', $file, $exception->getMessage())
            );
        }

        $namespace = '';

        foreach ($tokens as $index => $token) {
            if ($token->is([\T_COMMENT, \T_DOC_COMMENT, \T_WHITESPACE])) {
                continue;
            }

            if ($token->is(\T_NAMESPACE)
                && null !== ($nextToken = $tokens[$index + 2] ?? null)
                && $nextToken->is([\T_NAME_FULLY_QUALIFIED, \T_NAME_QUALIFIED])) {
                $namespace = $nextToken->text;

                continue;
            }

            if (\str_starts_with($namespace, $this->namespace)
                && null !== ($name = $this->getName($tokens, $token, $index))) {
                yield $key++ => $namespace.($namespace ? '\\' : '').$name; // @phpstan-ignore ternary.condNotBoolean, generator.valueType
            }
        }
    }

    /**
     * @param \PhpToken[] $tokens
     */
    private function getName(array $tokens, \PhpToken $token, int $currentIndex): ?string
    {
        if ($token->is(\T_CLASS)
            && null !== ($nextToken = $tokens[$currentIndex + 2] ?? null)) {
            $previousToken = $tokens[$currentIndex - 2] ?? null;

            if (null !== $previousToken && $previousToken->is(\T_ABSTRACT)) {
                return null;
            }

            return $nextToken->text;
        }

        if ($token->is(\T_INTERFACE)
            && null !== ($nextToken = $tokens[$currentIndex + 2] ?? null)) {
            return $nextToken->text;
        }

        return null;
    }
}
