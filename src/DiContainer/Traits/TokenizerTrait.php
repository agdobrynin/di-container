<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Traits;

use OutOfBoundsException;
use ParseError;

use function count;
use function is_array;
use function sprintf;
use function token_get_all;

use const TOKEN_PARSE;

trait TokenizerTrait
{
    /**
     * @var array<array{0: int, 1: string, 2: int}|string>
     */
    private array $tokens = [];
    private int $totalTokens = 0;

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

    /**
     * @return array{id: int, text: string, line: null|int}
     *
     * @throws OutOfBoundsException
     */
    private function parseToken(int $index): array
    {
        $this->tokenIsValid($index);

        if (is_array($this->tokens[$index])) {
            return [
                'id' => $this->tokens[$index][0],
                'text' => $this->tokens[$index][1],
                'line' => $this->tokens[$index][2],
            ];
        }

        return [
            'id' => 0,
            'text' => $this->tokens[$index],
            'line' => null,
        ];
    }

    private function tokenIsValid(int $index): void
    {
        if (0 < $index && $index >= $this->getTotalTokens()) {
            throw new OutOfBoundsException(
                sprintf('index must be between 0 and %d. Got: %d.', $this->getTotalTokens() - 1, $index)
            );
        }
    }
}
