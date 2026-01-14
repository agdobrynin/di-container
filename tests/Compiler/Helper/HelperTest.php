<?php

declare(strict_types=1);

namespace Tests\Compiler\Helper;

use Generator;
use Kaspi\DiContainer\Compiler\Helper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(Helper::class)]
class HelperTest extends TestCase
{
    #[DataProvider('dataProviderSuccess')]
    public function testConvertContainerIdentifierToMethodNameSuccess($prefix, $defaultName, $from, $to): void
    {
        self::assertEquals($to, Helper::convertContainerIdentifierToMethodName($from, $prefix, $defaultName));
    }

    public static function dataProviderSuccess(): Generator
    {
        yield 'fully qualified class name' => [
            'resolve_',
            'service',
            self::class,
            'resolve_helper_test',
        ];

        yield 'string ascii with doted' => [
            'resolve_',
            'service',
            'services.foo',
            'resolve_services_foo',
        ];

        yield 'string with symbols not valid for method name' => [
            'resolve_',
            'service',
            '111-222',
            'resolve_service',
        ];

        yield 'string with start symbols not valid for method name' => [
            'resolve_',
            'service',
            ',.~method',
            'resolve_method',
        ];

        yield 'string with some symbols not valid for method name' => [
            'resolve_',
            'service',
            'температура   -20',
            'resolve_температура____20',
        ];

        yield 'empty string' => [
            'resolve_',
            'service',
            '',
            'resolve_service',
        ];

        yield 'spaces string' => [
            'resolve_',
            'service',
            '  ',
            'resolve_service',
        ];
    }
}
