<?php

declare(strict_types=1);

namespace Tests\ContainerBuilder;

use Kaspi\DiContainer\DefinitionsLoader;
use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerBuilder;
use Kaspi\DiContainer\DiContainerNullConfig;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder;
use Kaspi\DiContainer\DiDefinition\Arguments\ArgumentResolver;
use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\Helper;
use Kaspi\DiContainer\Parameters\ImmediateSourceParameters;
use Kaspi\DiContainer\SourceDefinitions\AbstractSourceDefinitionsMutable;
use Kaspi\DiContainer\SourceDefinitions\ImmediateSourceDefinitionsMutable;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(DiContainerBuilder::class)]
#[CoversClass(DiContainerNullConfig::class)]
#[CoversClass(DefinitionsLoader::class)]
#[CoversClass(DiContainer::class)]
#[CoversClass(ArgumentBuilder::class)]
#[CoversClass(ArgumentResolver::class)]
#[CoversClass(DiDefinitionAutowire::class)]
#[CoversClass(Helper::class)]
#[CoversClass(ImmediateSourceParameters::class)]
#[CoversClass(AbstractSourceDefinitionsMutable::class)]
#[CoversClass(ImmediateSourceDefinitionsMutable::class)]
#[CoversFunction('Kaspi\DiContainer\diAutowire')]
class DiContainerBuilderConfiguratorContextTest extends TestCase
{
    public function testSetContexts(): void
    {
        vfsStream::setup(
            rootDirName: 'root',
            structure: [
                'config' => [
                    'services.php' => '<?php
    use Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface;
    
    return static function (DefinitionsConfiguratorInterface $config): \Generator {
        $value = $config->getContext("foo").":".$config->getContext("bar");
        /** @var \Tests\ContainerBuilder\Core $core */
        $core = $config->getContext(\Tests\ContainerBuilder\Core::class);
        
        yield \Kaspi\DiContainer\diAutowire(\Tests\ContainerBuilder\FooContext::class)
            ->bindArguments(
                foo: $value." - ".$core->str
            );
    };',
                ],
            ]
        );

        $core = new Core('Lorem ipsum');

        $container = (new DiContainerBuilder(
            new DiContainerNullConfig()
        ))
            ->setConfiguratorContext($core::class, $core)
            ->addConfiguratorContexts([
                'foo' => 'bar',
                'bar' => 'baz',
            ])
            ->load(vfsStream::url('root/config/services.php'))
            ->build()
        ;

        $fooContext = $container->get(FooContext::class);

        self::assertEquals('bar:baz - Lorem ipsum', $fooContext->foo);
    }
}

final class Core
{
    public function __construct(public readonly string $str) {}
}
final class FooContext
{
    public function __construct(public readonly string $foo) {}
}
