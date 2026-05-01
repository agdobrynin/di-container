<?php

declare(strict_types=1);

namespace Tests\Integration;

use Kaspi\DiContainer\DiContainerBuilder;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

use function bin2hex;
use function random_bytes;

/**
 * @internal
 */
#[CoversNothing]
class ContainerBuilderContextTest extends TestCase
{
    private DiContainerBuilder $builder;

    protected function setUp(): void
    {
        vfsStream::setup('root', structure: [
            'services.php' => '<?php
// definitions configuration file vfs://root/services.php
use Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface;
use function Kaspi\DiContainer\diAutowire;

return static function (DefinitionsConfiguratorInterface $configurator): \Generator {
    $core = $configurator->getContext(\Tests\Integration\Core::class);

    yield diAutowire(\Tests\Integration\FooContext::class)
        ->bindArguments(
            appEnv: $configurator->getContext("APP_ENV"),
            str: $core->str,
        );
};
',
        ]);

        $core = new Core('Lorem ipsum');

        $this->builder = (new DiContainerBuilder())
            ->setConfiguratorContext(Core::class, $core)
        ;

        $this->builder->load(vfsStream::url('root/services.php'));

        $this->builder->addConfiguratorContexts((static function () {
            yield 'APP_ENV' => 'test';
        })());
    }

    protected function tearDown(): void
    {
        unset($this->builder);
    }

    public function testUseContextOnRuntimeContainer(): void
    {
        $container = $this->builder->build();

        $foo = $container->get(FooContext::class);

        self::assertEquals('test', $foo->appEnv);
        self::assertEquals('Lorem ipsum', $foo->str);
    }

    public function testUseContextOnCompiledContainer(): void
    {
        $containerClass = 'Container'.bin2hex(random_bytes(16));
        $container = $this->builder
            ->addConfiguratorContexts(['APP_ENV' => 'prod'])
            ->compileToFile(
                vfsStream::url('root/'),
                __NAMESPACE__.'\\'.$containerClass,
                isExclusiveLockFile: false,
            )
            ->build()
        ;

        $foo = $container->get(FooContext::class);

        self::assertEquals('prod', $foo->appEnv);
        self::assertEquals('Lorem ipsum', $foo->str);
    }
}

final class Core
{
    public function __construct(public readonly string $str) {}
}

final class FooContext
{
    public function __construct(
        public readonly string $appEnv,
        public readonly string $str,
    ) {}
}
