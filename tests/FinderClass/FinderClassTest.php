<?php

declare(strict_types=1);

namespace Tests\FinderClass;

use Kaspi\DiContainer\Finder\FinderClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Kaspi\DiContainer\Finder\FinderClass
 *
 * @internal
 */
class FinderClassTest extends TestCase
{
    public static function dataProviderFinderClassConstructFail(): \Generator
    {
        yield 'empty string' => [
            '',
            'Argument $namespace must be end with symbol "\"',
        ];

        yield 'invalid namespace #1' => [
            '11App\\',
            'Argument $namespace must be compatible with PSR-4',
        ];

        yield 'invalid namespace #2' => [
            '   App\\',
            'Argument $namespace must be compatible with PSR-4',
        ];

        yield 'invalid namespace #3' => [
            '\\',
            'Argument $namespace must be compatible with PSR-4',
        ];
    }

    /**
     * @dataProvider dataProviderFinderClassConstructFail
     */
    public function testFinderClassConstructFail(string $namespace, string $expectMessage): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($expectMessage);

        new FinderClass($namespace, []);
    }
}
