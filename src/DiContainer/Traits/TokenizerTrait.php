<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Traits;

use ParseError;

use function count;
use function is_array;
use function token_get_all;

use const TOKEN_PARSE;

trait TokenizerTrait
{
    /**
     * @var array<array{0: int, 1: string, 2: int}|string>
     */
    private array $tokens;
    private int $totalTokens;

    /**
     * @throws ParseError
     */
    private function tokenizeCode(string $code): void
    {
        $this->tokens = token_get_all($code, TOKEN_PARSE);
        $this->totalTokens = count($this->tokens);
    }

    private function getTotalTokens(): int
    {
        return $this->totalTokens;
    }

    private function getTokenId(int $index): int
    {
        return is_array($this->tokens[$index]) ? $this->tokens[$index][0] : 0;
    }

    private function getTokenText(int $index): string
    {
        return is_array($this->tokens[$index]) ? $this->tokens[$index][1] : $this->tokens[$index];
    }

    private function getTokenLine(int $index): ?int
    {
        return is_array($this->tokens[$index]) ? $this->tokens[$index][2] : null;
    }
}
