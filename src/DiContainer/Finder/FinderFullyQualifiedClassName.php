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
            $tokens = \token_get_all($code, \TOKEN_PARSE);
        } catch (\ParseError $exception) {
            throw new \RuntimeException(
                \sprintf('Cannot parse code in file "%s". Reason: %s', $file, $exception->getMessage())
            );
        }

        $namespace = '';
        $isNamespace = $isAbstract = $isClassOrInterface = false;

        foreach ($tokens as $token) {
            $token_id = \is_array($token) ? $token[0] : null;

            if (null !== $token_id && \in_array($token_id, [\T_WHITESPACE, \T_COMMENT, \T_DOC_COMMENT, \T_OPEN_TAG], true)) {
                continue;
            }

            if (\T_NAMESPACE === $token_id) {
                $isNamespace = true;

                continue;
            }

            $token_text = \is_array($token) ? $token[1] : $token;

            if (null !== $token_id && $isNamespace && \in_array($token_id, [\T_NAME_FULLY_QUALIFIED, \T_NAME_QUALIFIED], true)) {
                $namespace = $token_text;
                $isNamespace = false;

                continue;
            }

            if (\T_ABSTRACT === $token_id) {
                $isAbstract = true;

                continue;
            }

            if ('}' === $token_text) {
                $isAbstract = false;

                continue;
            }

            if (!$isAbstract && null !== $token_id && \in_array($token_id, [\T_CLASS, \T_INTERFACE], true)) {
                $isClassOrInterface = true;

                continue;
            }

            /*
             * Class naming:
             * @see https://www.php.net/manual/ru/language.oop5.basic.php#language.oop5.basic.class
             */
            if ($isClassOrInterface
                && \str_starts_with($namespace, $this->namespace)
                && 1 === \preg_match('/^[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*$/', $token_text)) {
                $isAbstract = $isClassOrInterface = false;

                yield $key++ => $namespace.($namespace ? '\\' : '').$token_text; // @phpstan-ignore ternary.condNotBoolean, generator.valueType
            }
        }
    }
}
