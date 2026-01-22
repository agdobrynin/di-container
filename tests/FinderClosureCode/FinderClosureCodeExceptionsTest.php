<?php

declare(strict_types=1);

namespace Tests\FinderClosureCode;

use Closure;
use Generator;
use Kaspi\DiContainer\Finder\FinderClosureCode;
use LogicException;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionFunction;
use RuntimeException;
use Tests\FinderClosureCode\Fixture\Yoo;

/**
 * @internal
 */
#[CoversClass(FinderClosureCode::class)]
class FinderClosureCodeExceptionsTest extends TestCase
{
    public function testFunctionCantGetSourceFile(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Function defined in the PHP core or in a PHP extension');

        (new FinderClosureCode())->getCode((new ReflectionFunction('log'))->getClosure());
    }

    public function testFunctionCannotOpenSourceFile(): void
    {
        $f = vfsStream::newFile('service.php')
            ->withContent('<?php return ["a" => static fn () => true];')
            ->at(vfsStream::setup())
        ;
        $res = require $f->url();

        $this->assertInstanceOf(Closure::class, $res['a']);

        $f->chmod(0222); // Cannot read file. Permission deny.

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot parse code from file "vfs://root/service.php".');

        (new FinderClosureCode())->getCode($res['a']);
    }

    #[DataProvider('dataProviderNoneStatic')]
    public function testShortClosureNoneStatic(Closure $fn): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Anonymous function must be declared as static');

        (new FinderClosureCode())->getCode($fn);
    }

    public static function dataProviderNoneStatic(): Generator
    {
        yield 'short function' => [fn () => true];

        yield 'function' => [function () { return false; }];
    }

    public function testFunctionReferenceViaUse(): void
    {
        $a = 0;
        $fn = static function () use ($a) {return $a; };

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Anonymous function cannot use a reference variable via keyword "use".');

        (new FinderClosureCode())->getCode($fn);
    }

    public function testFunctionUseThis(): void
    {
        $fn = static fn () => $this->count();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Anonymous arrow function cannot use a reference variable via "$this".');

        (new FinderClosureCode())->getCode($fn);
    }

    public function testFunctionFromClassWithoutStaticDefinition(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Anonymous function must be declared as static');

        (new FinderClosureCode())->getCode((new Yoo('oka'))->getClosureFunction());
    }

    public function testShorFnFromClassWithThis(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Anonymous arrow function cannot use a reference variable via "$this"');

        (new FinderClosureCode())->getCode((new Yoo('oka'))->getClosureFn());
    }

    public function testShortFnFromClassWithParent(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Anonymous function cannot use a reference variable via keyword "PARENT".');

        echo (new FinderClosureCode())->getCode((new Yoo('oka'))->getClosureFnWithParent());
    }

    public function testFunctionCannotUseSelf(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Anonymous function cannot use a reference variable via keyword "self"');

        (new FinderClosureCode())->getCode(
            (new Yoo('x'))->getClosureUsingSelf()
        );
    }

    public function testFunctionCannotUseStatic(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Anonymous function cannot use a reference variable via keyword "static"');

        (new FinderClosureCode())->getCode(
            (new Yoo('x'))->getClosureUsingStatic()
        );
    }

    public function testFunctionCannotUseParent(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Anonymous function cannot use a reference variable via keyword "parent"');

        (new FinderClosureCode())->getCode(
            (new Yoo('x'))->getClosureWithParent()
        );
    }
}
