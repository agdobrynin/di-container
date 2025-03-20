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

use const T_STATIC;
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

        if (false === ($fileName = $reflection->getFileName())) {
            throw new RuntimeException(
                sprintf('Function defined in the PHP core or in a PHP extension. Got: %s.', var_export($function, true))
            );
        }

        try {
            $f = (new SplFileObject($fileName))->openFile('rb');
            $code = '';

            while (!$f->eof()) {
                $code .= $f->fread(8192);
            }

            $tokens = $this->fileTokens[$f->getPathname()] ??= token_get_all($code, TOKEN_PARSE);
        } catch (LogicException|ParseError|RuntimeException  $e) {
            throw new RuntimeException(
                sprintf('Cannot parse code from file "%s". Reason: %s', $fileName, $e->getMessage()),
                previous: $e
            );
        }

        $fnStart = $fnIsStatic = false;
        $fnLevel = 0;
        $fnTokens = [];

        for ($i = 0, $t = count($tokens); $i < $t; ++$i) {
            $tokenLine = $tokens[$i][2] ?? -1;

            if ($tokenLine < $reflection->getStartLine()) {
                continue;
            }

            $token_id = is_array($tokens[$i])
                ? $tokens[$i][0]
                : 0;

            if (T_STATIC === $token_id) {
                $fnIsStatic = true;

                continue;
            }

            if (in_array($token_id, [T_FN, T_FUNCTION], true)) {
                if (!$fnIsStatic) {
                    throw new LogicException(
                        sprintf('Function must be declare with "static" keyword. Code from file "%s".', $fileName),
                    );
                }

                $fnStart = true;
            }

            if ($fnStart) {
                if (T_USE === $token_id) {
                    throw new LogicException(
                        sprintf('Function cannot import variable vai keyword "use". Code from file "%s".', $fileName),
                    );
                }

                $fnTokens[] = is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];
            }
        }

        return implode($fnTokens);
    }
}
