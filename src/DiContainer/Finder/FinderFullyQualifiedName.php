<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Finder;

use Kaspi\DiContainer\Interfaces\Finder\FinderFullyQualifiedNameInterface;
use Kaspi\DiContainer\Traits\TokenizerTrait;

/**
 * @phpstan-import-type ItemFQN from FinderFullyQualifiedNameInterface
 */
final class FinderFullyQualifiedName implements FinderFullyQualifiedNameInterface
{
    use TokenizerTrait;

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
     * @return \Generator<non-negative-int, ItemFQN>
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
            $this->tokenizeCode($code);
        } catch (\ParseError $exception) {
            throw new \RuntimeException(
                \sprintf('Cannot parse code in file "%s". Reason: %s', $file, $exception->getMessage())
            );
        }

        $namespace = '';
        $isValidFqn = true;
        $level = 0;
        $fqnLevel = null;

        for ($i = 0; $i < $this->getTotalTokens(); ++$i) {
            $token_id = $this->getTokenId($i);
            $token_text = $this->getTokenText($i);

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

            if (\T_NAMESPACE === $token_id) {
                for (++$i; $i < $this->getTotalTokens(); ++$i) {
                    $token_id = $this->getTokenId($i);

                    if (\in_array($token_id, [\T_STRING, \T_NAME_QUALIFIED, \T_NAME_FULLY_QUALIFIED], true)) {
                        $namespace = $this->getTokenText($i);

                        break;
                    }
                }
            }

            if (\in_array($token_id, [\T_ABSTRACT, \T_TRAIT], true)) {
                $isValidFqn = false;
            }

            if ($isValidFqn && \in_array($token_id, [\T_CLASS, \T_INTERFACE], true)) {
                $fqnItem = [];
                $classOrInterfaceTokenId = $token_id;

                for (++$i; $i < $this->getTotalTokens(); ++$i) {
                    $token_id = $this->getTokenId($i);
                    $token_text = $this->getTokenText($i);

                    /*
                     * Class, interface naming for $token_text:
                     * @see https://www.php.net/manual/ru/language.oop5.basic.php#language.oop5.basic.class
                     */
                    if ([] === $fqnItem
                        && \T_STRING === $token_id
                        && \str_starts_with($namespace, $this->namespace)
                        && 1 === \preg_match('/^[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*$/', $token_text)) {
                        /** @var class-string $fqn */
                        $fqn = \implode('\\', [$namespace, $token_text]);
                        $fqnItem = [
                            'fqn' => $fqn,
                            'tokenId' => $classOrInterfaceTokenId,
                            'line' => $this->getTokenLine($i),
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
