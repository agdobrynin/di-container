<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Finder;

use Closure;
use ParseError;
use ReflectionException;
use RuntimeException;

final class FinderFunctionCode
{
    private array $fileTokens = [];

    /**
     * @throws ReflectionException
     * @throws RuntimeException
     */
    public function getCode(string|Closure $function): string
    {
        $reflection = new \ReflectionFunction($function);

        if (false === ($fileName = $reflection->getFileName())) {
            throw new RuntimeException(
                sprintf('Function defined in the PHP core or in a PHP extension. Got: %s.', var_export($function, true))
            );
        }

        try {
            $file = new \SplFileObject($fileName);
            $f = $file->openFile('rb');
            $code = '';

            while (!$f->eof()) {
                $code .= $f->fread(8192);
            }

            $tokens = $this->fileTokens[$f->getPathname()] ??= token_get_all($code, TOKEN_PARSE);
        } catch (ParseError|RuntimeException $exception) {
            throw new RuntimeException(
                sprintf('Cannot parse code from file "%s". Reason: %s', $fileName, $exception->getMessage())
            );
        }

        $fnStart = false;
        $fnTokens = [];

        for ($i = 0, $t = count($tokens); $i < $t; $i++) {
            $tokenLine = $tokens[$i][2] ?? -1;

            if ($tokenLine < $reflection->getStartLine()) {
                continue;
            }

            [$token_id, $token_text] = is_array($tokens[$i])
                ? [$tokens[$i][0], $tokens[$i][1]]
                : [0, $tokens[$i]];

            if (in_array($token_id, [T_FN, T_FUNCTION], true)) {
                $fnStart = true;
            }

            if ($fnStart) {
                if ($token_id)
                $fnTokens[] = $tokens[$i];
            }
        }

        return implode($fnTokens);
    }
}
