<?php

declare(strict_types=1);

namespace Tests\Integration\ContainerParameter;

use Generator;
use Kaspi\DiContainer\DiContainerBuilder;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\Enum\InvalidBehaviorCompileEnum;
use Kaspi\DiContainer\Interfaces\Compiler\Exception\CompiledContainerExceptionInterface;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Tests\Integration\ContainerParameter\Fixtures\Foo;
use Tests\Integration\ContainerParameter\Fixtures\FooAttr;

use function bin2hex;
use function random_bytes;
use function strrpos;
use function substr;

/**
 * @internal
 */
#[CoversNothing]
class ContainerParameterCompiledTest extends TestCase
{
    public function testCompileParameterInConstructor(): void
    {
        vfsStream::setup();
        $containerClass = 'Container'.bin2hex(random_bytes(16));

        $container = (new DiContainerBuilder(
            new DiContainerConfig(
                useZeroConfigurationDefinition: false,
                useAttribute: true,
            )
        ))
            ->load(__DIR__.'/Fixtures/services.php')
            ->loadParameters(__DIR__.'/Fixtures/parameters.php')
            ->import('Tests\\', __DIR__.'/Fixtures')
            ->compileToFile(vfsStream::url('root'), $containerClass, isExclusiveLockFile: false)
            ->build()
        ;

        self::assertEquals(
            'example.com:8080',
            $container->get(Foo::class)->endpoint
        );
        self::assertEquals(
            'example.com:8080',
            $container->get(FooAttr::class)->endpoint
        );
    }

    #[DataProvider('provideCompileParameterNotRegistered')]
    public function testCompileParameterNotRegistered(ContainerInterface $container, string $class): void
    {
        $this->expectException(CompiledContainerExceptionInterface::class);
        $this->expectExceptionMessageMatches('/^The definition was not compiled.+\\'.substr($class, strrpos($class, '\\')).'/');

        $container->get($class);
    }

    public static function provideCompileParameterNotRegistered(): Generator
    {
        vfsStream::setup();
        $containerClass = 'Container'.bin2hex(random_bytes(16));

        $container = (new DiContainerBuilder(
            new DiContainerConfig(
                useZeroConfigurationDefinition: false,
                useAttribute: true,
            )
        ))
            ->load(__DIR__.'/Fixtures/services_not_set_parameter_port.php')
            ->loadParameters(__DIR__.'/Fixtures/parameters.php')
            ->import('Tests\\', __DIR__.'/Fixtures')
            ->compileToFile(
                vfsStream::url('root'),
                $containerClass,
                isExclusiveLockFile: false,
                options: [
                    'invalid_behavior' => InvalidBehaviorCompileEnum::RuntimeContainerException,
                ]
            )
            ->build()
        ;

        yield 'Configured via php definition' => [
            $container,
            Foo::class,
        ];

        yield 'Configured via php attribute' => [
            $container,
            FooAttr::class,
        ];
    }
}
