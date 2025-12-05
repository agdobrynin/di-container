<?php

declare(strict_types=1);

namespace Tests\FinderClosureCode;

use Kaspi\DiContainer\Attributes\DiFactory;
use Kaspi\DiContainer\Attributes\Tag;
use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiDefinition\DiDefinitionCallable;
use Kaspi\DiContainer\Finder\FinderClosureCode;
use Kaspi\DiContainer\Interfaces\DiContainerSetterInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use Tests\FinderClosureCode\Fixture\SomeTrait;
use Tests\FinderClosureCode\Fixture\Y;

use function dirname;

/**
 * @internal
 */
#[CoversFunction('\Kaspi\DiContainer\diCallable')]
#[CoversClass(FinderClosureCode::class)]
#[CoversClass(DiDefinitionCallable::class)]
class FinderClosureCodeTest extends TestCase
{
    private static array $namespace_brace_services1;
    private static array $namespace_services1;
    private static array $namespace_root_brace_services;
    private static array $root_namespace;
    private static array $use_brace_and_comma_service1;
    private static array $magic_constants;
    private static array $multi_namespace_closure;

    public static function setUpBeforeClass(): void
    {
        static::$namespace_brace_services1 ??= require __DIR__.'/Fixture/namespace_brace_services1.php';
        static::$namespace_services1 ??= require __DIR__.'/Fixture/namespace_services1.php';
        static::$namespace_root_brace_services ??= require __DIR__.'/Fixture/namespace_root_brace_services.php';
        static::$root_namespace ??= require __DIR__.'/Fixture/root_namespace.php';
        static::$use_brace_and_comma_service1 ??= require __DIR__.'/Fixture/use_brace_and_comma_service1.php';
        static::$magic_constants ??= require __DIR__.'/Fixture/magic_constants.php';
        static::$multi_namespace_closure ??= require __DIR__.'/Fixture/multi_namespace_closure.php';
    }

    public function testFunctionWithInlineNamespaceBaz(): void
    {
        $finderClosure = new FinderClosureCode();

        $code = $finderClosure->getCode(static::$namespace_services1['service.baz_foo']);
        $expectCode = <<< 'EXPECT'
static fn (\Baz\Foo $foo): \Baz\Foo => $foo
EXPECT;

        self::assertEquals($expectCode, $code);
    }

    public function testFunctionWithInlineNamespaceFooBar(): void
    {
        $finderClosure = new FinderClosureCode();
        $code1 = $finderClosure->getCode(static::$namespace_services1['service.bar.foo']);

        $fixDir = dirname(__DIR__, 2);

        $expect1 = <<< EXPECT1
static function (string \$param): \\Foo\\Bar\\Foo {
        if (\\in_array(\$param, \\Foo\\Bar\\RANGE_STRING_AS_INT, true)) {
            return new \\Baz\\Foo('some_file', '');
        }

        return new \\Foo\\Bar\\Foo(
            '{$fixDir}/tests/FinderClosureCode/Fixture/namespace_services1.php',
            '\\\\Foo\\\\Bar'
        );
    }
EXPECT1;

        self::assertEquals($expect1, $code1);
    }

    public function testShorFunctionWithConst(): void
    {
        /** @var DiDefinitionCallable $definition */
        $definition = (require __DIR__.'/Fixture/services_1.php')()->current();
        $code = (new FinderClosureCode())->getCode($definition->getDefinition());

        $fixDir = dirname(__DIR__, 2);

        $expect = <<< EXPECT
static fn (\\Psr\\Container\\ContainerInterface \$container): mixed => \$container->has(\\Kaspi\\DiContainer\\Finder\\FinderClosureCode::class)
            ? \$container->get(\\Kaspi\\DiContainer\\Finder\\FinderClosureCode::class)
            : throw new \\Kaspi\\DiContainer\\Exception\\NotFoundException(\\sprintf('Const is "%s" in directory "%s"', \\Tests\\Fixtures\\LALA_LAND, '{$fixDir}/tests/FinderClosureCode/Fixture'))
EXPECT;

        self::assertEquals($expect, $code);
    }

    public function testNamespaceBrace(): void
    {
        $code = (new FinderClosureCode())->getCode(static::$namespace_brace_services1['service.crazy_bar']);

        $expectCode = <<< 'EXPECT'
static function (\Services\Foo\Bar $bar, \ArrayIterator $arrayIterator): \Services\Baz\Qux {
            $qux = new \Services\Baz\Qux(arrayIterator: $arrayIterator);

            return $bar->class(iterator: $qux->iterator());
        }
EXPECT;

        self::assertEquals($expectCode, $code);
    }

    public function testNamespaceBraceAndClosureFromClass(): void
    {
        $code = (new FinderClosureCode())->getCode(static::$namespace_brace_services1['service.closure_from_class']);

        $expectCode = <<< 'EXPECT'
static function (array $args): \Services\Foo\Bar {
                return new \Services\Foo\Bar(
                    new \Services\Baz\Qux(
                        arrayIterator: new \ArrayIterator($args)
                    )
                );
            }
EXPECT;

        self::assertEquals($expectCode, $code);
    }

    public function testClosureInSameNamespace(): void
    {
        $code = (new FinderClosureCode())->getCode(
            static function (Y $classY) {
                new A(new B(), [new C(), $classY]);
            }
        );

        $expect = 'static function (\Tests\FinderClosureCode\Fixture\Y $classY) {
                new \Tests\FinderClosureCode\A(new \Tests\FinderClosureCode\B(), [new \Tests\FinderClosureCode\C(), $classY]);
            }';

        self::assertEquals($code, $expect);
    }

    public function testDifferentNamespaceWithBrace(): void
    {
        $code = (new FinderClosureCode())->getCode(
            static::$namespace_root_brace_services['baz.bar.foo']
        );

        $expectCode = <<< 'EXPECT'
static function (): \Baz\Bar\Foo {
            \Foo::setup(params: ['foo' => '\\Tests\\FinderClosureCode\\Fixture']);

            return (new \Baz\Bar\Foo(
                'Something',
                params: [
                    'foo' => 'bar',
                    'bar' => 'baz'
                ]
            ))
                ->setup(
                    new \Faz\Bar\Foo(
                        str: 'yes',
                        foo: new \Baz\Bar\Foo(
                            'otherSomething',
                            params: [
                                'ozz' => true,
                            ]
                        )
                    )
                );
        }
EXPECT;

        self::assertEquals($expectCode, $code);
    }

    public function testClosureWithRootNamespace(): void
    {
        $code = (new FinderClosureCode())->getCode(static::$root_namespace['services.roots']);

        $expectCode = <<< 'EXPECT'
static fn (\A $a, \B\C $c, ?bool $isParsed = null) => $a->doMake($isParsed, '', c: $c)
EXPECT;

        self::assertEquals($expectCode, $code);
    }

    public function testAnonymousClassInClosureUseThisParentSelf(): void
    {
        $fn = static function (array $args) {
            return new class($args) extends DiContainer implements DiContainerSetterInterface, ContainerInterface {
                private const NS = __NAMESPACE__;

                public function set(string $id, $object): static
                {
                    parent::set($id.'-'.self::NS, $object);

                    return $this;
                }

                public function get(string $id): mixed
                {
                    return parent::get(id: $id);
                }
            };
        };

        $code = (new FinderClosureCode())->getCode($fn);
        $expectCode = <<< 'EXPECT'
static function (array $args) {
            return new class($args) extends \Kaspi\DiContainer\DiContainer implements \Kaspi\DiContainer\Interfaces\DiContainerSetterInterface, \Psr\Container\ContainerInterface {
                private const NS = '\\Tests\\FinderClosureCode';

                public function set(string $id, $object): static
                {
                    parent::set($id.'-'.self::NS, $object);

                    return $this;
                }

                public function get(string $id): mixed
                {
                    return parent::get(id: $id);
                }
            };
        }
EXPECT;

        self::assertEquals($expectCode, $code);
    }

    public function testCachedParseFileUses(): void
    {
        $fn = static fn () => true;
        $fn2 = static function (): Tag { return new Tag(name: 'ok'); };

        $fcc = new FinderClosureCode();
        $code1 = $fcc->getCode($fn);
        $code2 = $fcc->getCode($fn2);

        self::assertEquals('static fn () => true', $code1);
        self::assertEquals('static function (): \Kaspi\DiContainer\Attributes\Tag { return new \Kaspi\DiContainer\Attributes\Tag(name: \'ok\'); }', $code2);
    }

    public function testUseTraitInAnonymousClass(): void
    {
        $fn = static fn (ReflectionClass $reflectionClass) => new class {
            use SomeTrait;

            public function __construct(private ReflectionClass $reflectionClass) {}

            public function getDiFactory(): ?DiFactory
            {
                return $this->getDiFactoryAttribute($this->reflectionClass);
            }
        };

        $code = (new FinderClosureCode())->getCode($fn);
        $expectCode = <<< 'EXPECT'
static fn (\ReflectionClass $reflectionClass) => new class {
            use \Tests\FinderClosureCode\Fixture\SomeTrait;

            public function __construct(private \ReflectionClass $reflectionClass) {}

            public function getDiFactory(): ?\Kaspi\DiContainer\Attributes\DiFactory
            {
                return $this->getDiFactoryAttribute($this->reflectionClass);
            }
        }
EXPECT;

        self::assertEquals($expectCode, $code);
    }

    public function testUseBraceInImports(): void
    {
        $code = (new FinderClosureCode())->getCode(self::$use_brace_and_comma_service1['fn1']);

        $expectCode = <<< 'EXPECT'
static fn(\Foo\Bar $bar, \Foo\Baz $baz): array => [$bar, $baz]
EXPECT;

        self::assertEquals($expectCode, $code);
    }

    public function testUseSeparateByCommaAndSubNamespace(): void
    {
        $code = (new FinderClosureCode())->getCode(self::$use_brace_and_comma_service1['fn2']);

        $expectCode = <<< 'EXPECT'
static function (): array {
        return \array_map(
            null,
            \Bar\Foo::array_map(),
            (new \Baz\Qnx\Foo())->array_map(),
        );
    }
EXPECT;

        self::assertEquals($expectCode, $code);
    }

    public function testUseAliasesSeparateByCommaAbsoluteNamespace(): void
    {
        $code = (new FinderClosureCode())->getCode(self::$use_brace_and_comma_service1['fn3']);

        $expectCode = <<< 'EXPECT'
static function (\Qnx\Foo $foo): array {
        return $foo->array_map((new \Qnx\Bar\Fuzz())->toArray());
    }
EXPECT;

        self::assertEquals($expectCode, $code);
    }

    public function testMagicConstants(): void
    {
        $code = (new FinderClosureCode())->getCode(self::$magic_constants['fn1']);
        $fixDir = dirname(__DIR__, 2);

        $expectCode = <<< EXPECT
static function (): array {
        return [
            'T_DIR' => '{$fixDir}/tests/FinderClosureCode/Fixture',
            'T_FILE' => '{$fixDir}/tests/FinderClosureCode/Fixture/magic_constants.php',
            'T_LINE' => 11,
            'T_NS_C' => '\\\\Tests\\\\FinderClosureCode\\\\Fixture',
        ];
    }
EXPECT;

        self::assertEquals($expectCode, $code);
    }

    public function testNamedArgument(): void
    {
        $code = (new FinderClosureCode())->getCode(static::$root_namespace['services.roots.from_proj']);

        $expectCode = <<< 'EXPECT'
static function (
        \App\Environment $environment,
        \Psr\Log\LoggerInterface $logger,
    ) {
        $tempDir = $environment->getTempDirectory() . \DIRECTORY_SEPARATOR . 'cache';
        $cacheInterface = new \Symfony\Component\Cache\Adapter\FilesystemAdapter(
            directory: $tempDir
        );

        $cacheInterface->setLogger($logger);
        return $cacheInterface;
    }
EXPECT;

        self::assertEquals($expectCode, $code);
    }

    public function testMultiNamespace(): void
    {
        $fns = self::$multi_namespace_closure;

        $code1 = (new FinderClosureCode())->getCode($fns['fn1']);

        $expectCode1 = <<< 'EXPECT'
static fn(\Events\Foo $a, \Events\Bar $b, \App\Qux $q) => true
EXPECT;

        self::assertEquals($expectCode1, $code1);

        $code2 = (new FinderClosureCode())->getCode($fns['fn2']);

        $expectCode2 = <<< 'EXPECT'
static fn(\Services\Foo $a, \Services\Bar $b, \Services\Baz\Qux $q) => true
EXPECT;

        self::assertEquals($expectCode2, $code2);

        $code3 = (new FinderClosureCode())->getCode($fns['fn3']);

        $expectCode3 = <<< 'EXPECT'
static fn(\App\Foo $a, \App\Bar $b, \App\Baz $q) => true
EXPECT;

        self::assertEquals($expectCode3, $code3);

        $code4 = (new FinderClosureCode())->getCode($fns['fn4']);

        $expectCode4 = <<< 'EXPECT'
static fn(\Foo $a, \Bar $b, \Baz $q) => true
EXPECT;

        self::assertEquals($expectCode4, $code4);
    }
}
