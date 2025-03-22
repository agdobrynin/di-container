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
use function implode;
use function in_array;
use function is_array;
use function sprintf;
use function token_get_all;
use function var_export;

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

        for ($i = 0, $t = count($tokens); $i < $t; ++$i) {
            $token_id = is_array($tokens[$i]) ?
                $tokens[$i][0]
                : 0;

            if (false === $fnStart && T_USE === $token_id) {
                $useNameSpaceLevel = 0;

                for (++$i; $i < $t; ++$i) { // parse use namespace.
                    [$token_id, $token_text] = is_array($tokens[$i])
                        ? [$tokens[$i][0], $tokens[$i][1]]
                        : [0, $tokens[$i]];

                    if (';' === $token_text) {
                        break;
                    }

                    if ('{' === $token_text) {
                        ++$useNameSpaceLevel;
                    } elseif ('}' === $token_text) {
                        --$useNameSpaceLevel;
                    }

                    if (in_array($token_id, [T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED], true)) {
                        $useNamespace[] = $token_text;
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
