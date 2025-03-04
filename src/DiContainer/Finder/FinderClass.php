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
            if ($token->is(\T_NAMESPACE)
                && null !== ($nextToken = $tokens[$index + 2] ?? null)
                && $nextToken->is([\T_NAME_FULLY_QUALIFIED, \T_NAME_QUALIFIED])) {
                $namespace = $nextToken->text;

                continue;
            }

            if (\str_starts_with($namespace, $this->namespace)
                && null !== ($f_q_c_n = $this->getFQCN($tokens, $token, $index, $namespace))) {
                yield $keyOfClass++ => $f_q_c_n;
            }
        }
    }

    /**
     * @param \PhpToken[] $tokens
     *
     * @return null|class-string
     */
    private function getFQCN(array $tokens, \PhpToken $token, int $index, string $namespace): ?string
    {
        if ($token->is(\T_CLASS)
            && null !== ($nextToken = $tokens[$index + 2] ?? null)) {
            $previousToken = $tokens[$index - 2] ?? null;

            if (null !== $previousToken && $previousToken->is(\T_ABSTRACT)) {
                return null;
            }

            return $namespace.($namespace ? '\\' : '').$nextToken->text; // @phpstan-ignore return.type, ternary.condNotBoolean
        }

        if ($token->is(\T_INTERFACE)
            && null !== ($nextToken = $tokens[$index + 2] ?? null)) {
            return $namespace.($namespace ? '\\' : '').$nextToken->text; // @phpstan-ignore return.type, ternary.condNotBoolean
        }

        return null;
    }
}
