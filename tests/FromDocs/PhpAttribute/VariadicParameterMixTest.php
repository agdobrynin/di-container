<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpAttribute;

use Kaspi\DiContainer\Attributes\DiFactory;
use Kaspi\DiContainer\Attributes\Inject;
use Kaspi\DiContainer\Attributes\InjectByCallable;
use Kaspi\DiContainer\DiContainerBuilder;
use Kaspi\DiContainer\Interfaces\DiFactoryInterface;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * @internal
 */
#[CoversNothing]
class VariadicParameterMixTest extends TestCase
{
    public function testResolveMixedAttributes(): void
    {
        $container = (new DiContainerBuilder())
            ->addDefinitions([
                'service.foo_bar' => 'i am service foo_bar',
            ])
            ->build()
        ;

        $foo = $container->get(Foo::class);

        self::assertEquals('i am service foo_bar', $foo->getArgs()[0]);
        self::assertEquals('factory service', $foo->getArgs()[1]);
        self::assertMatchesRegularExpression('/[0-9a-z]{13}/', $foo->getArgs()[2]);
    }
}

class Foo
{
    private array $args;

    public function __construct(
        #[Inject('service.foo_bar')]
        #[DiFactory(ServiceOneFactory::class)]
        #[InjectByCallable('\uniqid')]
        mixed ...$args
    ) {
        $this->args = $args;
    }

    public function getArgs(): array
    {
        return $this->args;
    }
}

class ServiceOneFactory implements DiFactoryInterface
{
    public function __invoke(ContainerInterface $container): string
    {
        return 'factory service';
    }
}
