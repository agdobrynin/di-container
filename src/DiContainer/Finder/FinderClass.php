<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Finder;

use Kaspi\DiContainer\Interfaces\Finder\FinderClassInterface;

final class FinderClass implements FinderClassInterface
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

    public function getClasses(): \Iterator
    {
        $keyOfClass = 0;

        foreach ($this->files as $file) {
            yield from $this->getClassesInFile($file, $keyOfClass);
        }
    }

    /**
     * @param non-negative-int $keyOfClass
     *
     * @return \Generator<non-negative-int, class-string>
     *
     * @throws \RuntimeException
     */
    private function getClassesInFile(\SplFileInfo $file, int &$keyOfClass): \Generator
    {
        if ($code = $file->getRealPath()) {
            $code = @\file_get_contents($file->getRealPath());
        }

        if (false === $code) {
            throw new \RuntimeException(
                \sprintf('Cannot get file contents from "%s". Reason: %s', $file, \error_get_last()['message'] ?? 'Unknown')
            );
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
            if ($token->is(\T_NAMESPACE)
                && null !== ($nextToken = $tokens[$index + 2] ?? null)
                && $nextToken->is([\T_NAME_FULLY_QUALIFIED, \T_NAME_QUALIFIED])) {
                $namespace = $nextToken->text;

                continue;
            }

            if ($token->is(\T_CLASS)
                && null !== ($nextToken = $tokens[$index + 2] ?? null)
                && \str_starts_with($namespace, $this->namespace)) {
                $previousToken = $tokens[$index - 2] ?? null;

                if ((null !== $previousToken && $previousToken->is(\T_ABSTRACT))
                    || !\str_starts_with($namespace, $this->namespace)) {
                    continue;
                }

                yield $keyOfClass++ => $namespace.($namespace ? '\\' : '').$nextToken->text; // @phpstan-ignore generator.valueType, ternary.condNotBoolean
            }
        }
    }
}
