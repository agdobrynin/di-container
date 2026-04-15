<?php

declare(strict_types=1);

namespace Tests\SourceParameters;

use Generator;

use function define;

class ParameterDataset
{
    public static function notFound(): Generator
    {
        yield 'empty params' => [
            [],
            'foo',
            '/^Parameter name "foo" not found\.$/',
        ];

        yield 'partial value by name' => [
            [
                'foo' => 'bar:{baz}',
            ],
            'foo',
            '/container parameter "foo":.+placeholder "{baz}"\. Parameter name "baz" not found/',
        ];

        yield 'partial value in multilevel array' => [
            [
                'foo' => [
                    'bar:{port}' => [
                        'qux{hash}',
                    ],
                ],
                'port' => 25_25,
            ],
            'foo',
            '/container parameter "foo":.+placeholder "{hash}"\. Parameter name "hash" not found/',
        ];

        yield 'chain in resolving not found' => [
            [
                'foo' => 'bar:{baz}',
                'baz' => 'bar:{qux}',
                'qux' => 'bar:{hash}',
            ],
            'foo',
            '/parameters "foo" -> "baz" -> "qux".+placeholder "{hash}"\. Parameter name "hash" not found/',
        ];
    }

    public static function successAndCaching(): Generator
    {
        yield 'plain params and escaped symbols' => [
            [
                'foo' => 'bar:{baz}',
                'baz' => '{{bat}',
                'quux' => '{foo}|{baz}',
                'quuux' => 'Lorem{{{{some}}',
                'FOO' => '{baz}{{baz}{baz}',
                'param.float' => 10.2,
                'param.int' => 10,
                'param.concat' => '{baz}|{param.float}|{param.int}',
            ],
            [
                'foo' => 'bar:{bat}',
                'baz' => '{bat}',
                'quux' => 'bar:{bat}|{bat}',
                'quuux' => 'Lorem{{some}}',
                'FOO' => '{bat}{baz}{bat}',
                'param.float' => 10.2,
                'param.int' => 10,
                'param.concat' => '{bat}|10.2|10',
            ],
        ];

        yield 'multilevel array with params' => [
            [
                'debug_mode' => APP_DEBUG_MODE,
                'foo' => [
                    'bar:{baz}' => [
                        'hash' => '{hash}',
                        'params' => [
                            '{port}',
                            '{secure}',
                            ParamFromClassConst::FIRST,
                        ],
                    ],
                    'enum' => ParamEnum::SECOND,
                ],
                'baz' => 'bat',
                'hash' => 'random_string',
                'port' => 25_25,
                'secure' => 0,
                'null' => null,
            ],
            [
                'debug_mode' => true,
                'foo' => [
                    'bar:bat' => [
                        'hash' => 'random_string',
                        'params' => [
                            '2525',
                            '0',
                            'first',
                        ],
                    ],
                    'enum' => ParamEnum::SECOND,
                ],
                'baz' => 'bat',
                'hash' => 'random_string',
                'port' => 25_25,
                'secure' => 0,
                'null' => null,
            ],
        ];

        yield 'parameter link as one to one' => [
            [
                'foo' => '{baz}',
                'baz' => true,
                'qux' => '{quux}',
                'quux' => [
                    'string' => '{bat}',
                    'number' => 1_000,
                    'bool' => '{baz}',
                ],
                'bat' => 'random_string',
            ],
            [
                'foo' => true,
                'baz' => true,
                'qux' => [
                    'string' => 'random_string',
                    'number' => 1000,
                    'bool' => true,
                ],
                'quux' => [
                    'string' => 'random_string',
                    'number' => 1000,
                    'bool' => true,
                ],
                'bat' => 'random_string',
            ],
        ];
    }
}

enum ParamEnum: string
{
    case FIRST = 'first';
    case SECOND = 'second';
}

class ParamFromClassConst
{
    public const FIRST = 'first';
}

define('APP_DEBUG_MODE', true);
