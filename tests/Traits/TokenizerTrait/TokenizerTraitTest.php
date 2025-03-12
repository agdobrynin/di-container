<?php

declare(strict_types=1);

namespace Tests\Traits\TokenizerTrait;

use Generator;
use Kaspi\DiContainer\Traits\TokenizerTrait;
use OutOfBoundsException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Kaspi\DiContainer\Traits\TokenizerTrait
 *
 * @internal
 */
class TokenizerTraitTest extends TestCase
{
    use TokenizerTrait;

    public function dataProvider(): Generator
    {
        yield 'negative index' => [-1, 99];

        yield 'too large index' => [100, 99];
    }

    /**
     * @dataProvider dataProvider
     */
    public function testTokenIsValid(int $index, int $total): void
    {
        $this->totalTokens = $total;

        $this->expectException(OutOfBoundsException::class);

        $this->parseToken($index);
    }
}
