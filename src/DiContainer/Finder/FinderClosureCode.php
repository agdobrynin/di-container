<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Finder;

use Closure;
use LogicException;
use ParseError;
use ReflectionException;
use ReflectionFunction;
use RuntimeException;
use SplFileObject;

use function count;
use function end;
use function implode;
use function in_array;
use function is_array;
use function print_r;
use function sprintf;
use function str_repeat;
use function token_get_all;
use function var_export;

use const PHP_EOL;
use const T_AS;
use const T_CONST;
use const T_FUNCTION;
use const T_USE;

final class FinderClosureCode
{
    /** @var array<string, list<array{0: int, 1: string, 2: int}|string>> */
    private array $fileTokens = [];

    /**
     * @throws RuntimeException
     * @throws LogicException
     */
    public function getCode(Closure $function): string
    {
        try {
            $reflection = new ReflectionFunction($function);
        } catch (ReflectionException $e) {
            throw new RuntimeException($e->getMessage(), previous: $e);
        }

        try {
            $tokens = $this->getTokens($reflection->getFileName());
        } catch (RuntimeException $e) {
            throw new RuntimeException(
                sprintf('%s Got: %s', $e->getMessage(), var_export($function, true)),
                previous: $e
            );
        }

        $fnStart = false;
        $fnLevel = $fnType = 0;
        $fnTokens = [];
        $useNamespace = [];
        $useNamespaceAlias = [];
        $useCount = 0;
        $isAlias = false;

        for ($i = 0, $t = count($tokens); $i < $t; ++$i) {
            $token_id = is_array($tokens[$i]) ?
                $tokens[$i][0]
                : 0;

            if (false === $fnStart && T_USE === $token_id) {
                $useNameSpaceLevel = 0;
                $useTypeId = 0;

                for (++$i; $i < $t; ++$i) { // parse use namespace.
                    [$token_id, $token_text] = is_array($tokens[$i])
                        ? [$tokens[$i][0], $tokens[$i][1]]
                        : [0, $tokens[$i]];

                    if (';' === $token_text) {
                        ++$useCount;
                        $isAlias = false;

                        break;
                    }

                    if (in_array($token_id, [T_FUNCTION, T_CONST], true)) {
                        $useTypeId = $token_id;

                        continue;
                    }

                    if ($isAlias && ',' === $token_text) {
                        $isAlias = false;

                        continue;
                    }

                    if ('{' === $token_text) {
                        ++$useNameSpaceLevel;

                        continue;
                    }
                    if ('}' === $token_text) {
                        --$useNameSpaceLevel;

                        continue;
                    }

                    if (T_AS === $token_id) {
                        $isAlias = true;

                        continue;
                    }

                    if ($isAlias && \T_STRING === $token_id) {
                        $useNamespaceAlias[$token_text] = end($useNamespace);

                        continue;
                    }

                    if (false === $isAlias && in_array($token_id, [T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED], true)) {
                        $useNamespace[$useCount][
                            match ($useTypeId) {
                                T_FUNCTION => 'function',
                                T_CONST => 'const',
                                default => ''
                            }
                        ][$useNameSpaceLevel][] = $token_text;
                    }
                }
            }

            if (is_array($tokens[$i])
                && (
                    $tokens[$i][2] < $reflection->getStartLine()
                    || in_array($tokens[$i][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)
                )
            ) {
                continue;
            }

            if (in_array($token_id, [T_FN, T_FUNCTION], true)) {
                $fnType = $token_id;

                continue;
            }

            if (0 !== $fnType && T_USE === $token_id) {
                throw new LogicException(
                    sprintf('Function cannot import variable via keyword "use". Code from file "%s".', $reflection->getFileName()),
                );
            }

            $token_text = is_array($tokens[$i])
                ? $tokens[$i][1]
                : $tokens[$i];

            if (0 !== $fnType && (T_DOUBLE_ARROW === $token_id || '{' === $token_text)) {
                $fnStart = true;
                ++$fnLevel;

                continue;
            }

            if ($fnStart) {
                if ('{' === $token_text) {
                    ++$fnLevel;
                } elseif ('}' === $token_text) {
                    --$fnLevel;
                }

                if (0 === $fnLevel && in_array($token_text, [',', ')', '}', ';', ']'], true)) {
                    break;
                }

                $fnTokens[] = $token_text;
            }
        }
        echo "\n\n";
        $normalizedNamespaces = (static function ($useNamespace) {
            return [];
        })($useNamespace);
        print_r($useNamespace);
        echo str_repeat('-', 20).PHP_EOL;
        print_r($useNamespaceAlias);

        return implode($fnTokens);
    }

    /**
     * @return array<string, list<array{0: int, 1: string, 2: int}|string>>
     */
    private function getTokens(false|string $fileName): array
    {
        if (isset($this->fileTokens[$fileName])) {
            return $this->fileTokens[$fileName];
        }

        if (false === $fileName) {
            throw new RuntimeException('Function defined in the PHP core or in a PHP extension.');
        }

        try {
            $f = (new SplFileObject($fileName))->openFile('rb');
            $code = '';

            while (!$f->eof()) {
                $code .= $f->fread(8192);
            }

            return $this->fileTokens[$f->getPathname()] = token_get_all($code, TOKEN_PARSE);
        } catch (LogicException|ParseError|RuntimeException  $e) {
            throw new RuntimeException(
                sprintf('Cannot parse code from file "%s". Reason: %s', $fileName, $e->getMessage()),
                previous: $e
            );
        }
    }
}
