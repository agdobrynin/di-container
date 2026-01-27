<?php

declare(strict_types=1);

namespace Tests\Integration;

use Kaspi\DiContainer\DiContainerBuilder;
use Kaspi\DiContainer\Enum\InvalidBehaviorCompileEnum;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Throwable;

use function bin2hex;
use function Kaspi\DiContainer\diGet;
use function random_bytes;

/**
 * @internal
 */
#[CoversNothing]
class HasAndNotFoundIdTestTest extends TestCase
{
    public function testFalsePositiveHasId(): void
    {
        vfsStream::setup();
        $containerClass = 'ContainerTest'.bin2hex(random_bytes(5));

        $container = (new DiContainerBuilder())
            ->addDefinitions([
                'foo' => diGet('none_exist_identifier'),
            ])
            ->compileToFile(
                vfsStream::url('root/'),
                'App\\'.$containerClass,
                isExclusiveLockFile: false,
                options: [
                    'invalid_behavior' => InvalidBehaviorCompileEnum::RuntimeContainerException,
                ]
            )
            ->build()
        ;

        try {
            $container->get('foo');
        } catch (Throwable $e) {
            self::assertInstanceOf(ContainerExceptionInterface::class, $e);
        }

        self::assertTrue($container->has('foo'));
        self::assertFalse($container->has('none_exist_identifier'));

        try {
            $container->get('none_exist_identifier');
        } catch (Throwable $e) {
            self::assertInstanceOf(NotFoundExceptionInterface::class, $e);
        }
    }
}
