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
        $isNamespace = $isAbstract = $isClassOrInterface = false;

        foreach ($tokens as $token) {
            if ($token->isIgnorable()) {
                continue;
            }

            if ($token->is(\T_NAMESPACE)) {
                $isNamespace = true;

                continue;
            }

            if ($isNamespace && $token->is([\T_NAME_FULLY_QUALIFIED, \T_NAME_QUALIFIED])) {
                $namespace = $token->text;
                $isNamespace = false;

                continue;
            }

            if ($token->is(\T_ABSTRACT)) {
                $isAbstract = true;

                continue;
            }

            if ('}' === $token->text) {
                $isAbstract = false;

                continue;
            }

            if (!$isAbstract && $token->is([\T_CLASS, \T_INTERFACE])) {
                $isClassOrInterface = true;

                continue;
            }

            /*
             * Class naming:
             * @see https://www.php.net/manual/ru/language.oop5.basic.php#language.oop5.basic.class
             */
            if ($isClassOrInterface
                && \str_starts_with($namespace, $this->namespace)
                && 1 === \preg_match('/^[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*$/', $token->text)) {
                $isAbstract = $isClassOrInterface = false;

                yield $key++ => $namespace.($namespace ? '\\' : '').$token->text; // @phpstan-ignore ternary.condNotBoolean, generator.valueType
            }
        }
    }
}
