<?php

declare(strict_types=1);

namespace Tests\ContainerBuilder;

use Kaspi\DiContainer\DefinitionsLoader;
use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\DiContainerBuilder;
use Kaspi\DiContainer\DiContainerNullConfig;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerBuilderExceptionInterface;
use Kaspi\DiContainer\Parameters\AbstractSourceParameters;
use Kaspi\DiContainer\Parameters\ImmediateSourceParameters;
use Kaspi\DiContainer\SourceDefinitions\ImmediateSourceDefinitionsMutable;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(DiContainerBuilder::class)]
#[CoversClass(DefinitionsLoader::class)]
#[CoversClass(DiContainer::class)]
#[CoversClass(DiContainerNullConfig::class)]
#[CoversClass(AbstractSourceParameters::class)]
#[CoversClass(ImmediateSourceParameters::class)]
#[CoversClass(ImmediateSourceDefinitionsMutable::class)]
class DiContainerBuilderParametersTest extends TestCase
{
    public function testUseConfiguratorForParameters(): void
    {
        vfsStream::setup(
            rootDirName: 'root',
            structure: [
                'parameters' => [
                    'params1.php' => '<?php return ["foo" => "bar", "is_local" => true];',
                    'params2.php' => '<?php return static function () { yield "baz" => "bat"; };',
                    'params3.php' => '<?php return ["api.endpoint" => "{api.host}:{api.port}"];',
                ],
                'services' => [
                    'service.php' => '<?php
    use Kaspi\DiContainer\Interfaces\DefinitionsConfiguratorInterface;
    
    return static function (DefinitionsConfiguratorInterface $config): void {
        $config->removeParameter("foo");
        
        $config->loadParameters("vfs://root/parameters/params3.php");
        
        if ($config->hasParameter("is_local")) {
            $config->addParameters([
                "api.port" => 8080,
                "api.host" => "https://localhost",
            ]);
        } else {
            $config->addParameters([
                "api.port" => 443,
                "api.host" => "https://example.com",
            ]);
        }
    };',
                ],
            ]
        );

        $container = (new DiContainerBuilder(
            new DiContainerNullConfig()
        ))
            ->loadParameters(
                'vfs://root/parameters/params1.php',
                'vfs://root/parameters/params2.php',
            )
            ->addParameters([
                'params.one' => 1,
                'params.two' => 2,
            ])
            ->setParameter('params.enum', ConfigTestEnum::TWO)
            ->load('vfs://root/services/service.php')
            ->build()
        ;

        self::assertEquals(
            [
                'is_local' => true,
                'baz' => 'bat',
                'params.one' => 1,
                'params.two' => 2,
                'params.enum' => ConfigTestEnum::TWO,
                'api.endpoint' => 'https://localhost:8080',
                'api.port' => 8080,
                'api.host' => 'https://localhost',
            ],
            [...$container->parameters()->parameters()]
        );
    }

    public function testCannotLoadParametersFromFile(): void
    {
        $this->expectException(ContainerBuilderExceptionInterface::class);

        vfsStream::setup(structure: [
            'parameters' => [
                'params1.php' => '<?php
return ["foo" => "bar", "is_local" => true];',
                'params2.php' => '<?php
// no set return keyword - fire exception
static function () { yield "baz" => "bat"; };
',
            ],
        ]);

        (new DiContainerBuilder(
            new DiContainerNullConfig()
        ))
            ->loadParameters(
                'vfs://root/parameters/params1.php',
                'vfs://root/parameters/params2.php',
            )
            ->build()
        ;
    }
}

enum ConfigTestEnum: int
{
    case ONE = 1;
    case TWO = 2;
}
