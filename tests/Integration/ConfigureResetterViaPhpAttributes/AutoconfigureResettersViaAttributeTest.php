<?php

declare(strict_types=1);

namespace Tests\Integration\ConfigureResetterViaPhpAttributes;

use Kaspi\DiContainer\DiContainerBuilder;
use Kaspi\DiContainer\DiContainerConfig;
use Kaspi\DiContainer\ObjectResetters;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Tests\Integration\ConfigureResetterViaPhpAttributes\Fixtures\Bar;
use Tests\Integration\ConfigureResetterViaPhpAttributes\Fixtures\Foo;

use function bin2hex;
use function random_bytes;

/**
 * @internal
 */
#[RequiresPhp('>= 8.5')]
#[CoversNothing]
class AutoconfigureResettersViaAttributeTest extends TestCase
{
    private DiContainerBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new DiContainerBuilder(
            new DiContainerConfig(
                useAttribute: true,
                isConfigureObjectResettersFromDefinitions: true,
            )
        );

        $this->builder->import('Tests\\', __DIR__.'/Fixtures');
    }

    protected function tearDown(): void
    {
        unset($this->builder);
    }

    #[TestWith([false])]
    #[TestWith([true])]
    public function testAutoconfigureConfigure(bool $compile): void
    {
        if ($compile) {
            $containerClass = 'Container'.bin2hex(random_bytes(5));

            vfsStream::setup('app');
            $this->builder->compileToFile(
                vfsStream::url('app'),
                'App\\'.$containerClass,
                isExclusiveLockFile: false,
            );
        }

        $container = $this->builder->build();

        // set runtime definition
        $bar = new Bar();
        $container->set($bar::class, $bar);

        self::assertEquals('Lorem ipsum bar', $container->get(Bar::class)->getVal());
        self::assertEquals('Lorem ipsum foo', $container->get(Foo::class)->getVal());

        $container->get(ObjectResetters::class)
            ->reset()
        ;

        self::assertEquals('str', $container->get(Bar::class)->getVal());
        self::assertEquals('', $container->get(Foo::class)->getVal());
    }
}
