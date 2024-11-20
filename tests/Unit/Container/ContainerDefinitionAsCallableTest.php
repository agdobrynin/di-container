<?php

declare(strict_types=1);

namespace Tests\Unit\Container;

use Kaspi\DiContainer\DiContainerFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Tests\Fixtures\Classes\ClassWithStaticMethods;
use Tests\Fixtures\Classes\ServiceLocation;

use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diCallable;
use function Kaspi\DiContainer\diReference;

/**
 * @covers \Kaspi\DiContainer\Attributes\DiFactory
 * @covers \Kaspi\DiContainer\Attributes\InjectContext
 * @covers \Kaspi\DiContainer\diAutowire
 * @covers \Kaspi\DiContainer\diCallable
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionCallable
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionReference
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionValue
 * @covers \Kaspi\DiContainer\diReference
 * @covers \Kaspi\DiContainer\Traits\ParametersResolverTrait::getParameterTypeByReflection
 *
 * @internal
 */
class ContainerDefinitionAsCallableTest extends TestCase
{
    public function testCallableDefinitionClassWithStaticMethod(): void
    {
        $container = (new DiContainerFactory())->make([
            'dictionary_welcome_message' => [
                'en' => 'welcome to site',
                'fr' => 'bienvenue sur le site',
            ],
            'welcome_message' => diCallable(
                ClassWithStaticMethods::class.'::langWelcomeMessage',
                [
                    'dict' => diReference('dictionary_welcome_message'),
                    'lang' => 'fr',
                ]
            ),
        ]);

        $res = $container->get('welcome_message');

        $this->assertEquals('bienvenue sur le site', $res);
    }

    public function testCallableDefinitionClassWithStaticMethodAndIsSingleton(): void
    {
        $container = (new DiContainerFactory())->make([
            'dictionary_welcome_message' => [
                'en' => 'welcome to site',
                'ru' => 'добро пожаловать на сайт',
                'fr' => 'bienvenue sur le site',
            ],
            'welcome_message' => diCallable(ClassWithStaticMethods::class.'::langWelcomeMessage', isSingleton: true)
                ->addArgument('dict', diReference('dictionary_welcome_message'))
                ->addArgument('lang', 'ru'),
        ]);

        $res = $container->get('welcome_message');

        $this->assertEquals('добро пожаловать на сайт', $res);
    }

    public function testCallableDefinitionWithNullDefinition(): void
    {
        $container = (new DiContainerFactory())->make();
        $this->expectException(ContainerExceptionInterface::class);

        $container->get(ClassWithStaticMethods::class.'::doSomething');
    }

    public function testCallableDefinitionWithDefinitionAndResolveArgumentInMethod(): void
    {
        $container = (new DiContainerFactory())->make([
            'doSomething' => ClassWithStaticMethods::class.'::doSomething',
            diAutowire(ServiceLocation::class, ['city' => 'Vice city']),
        ]);

        $res = $container->get('doSomething');

        $this->assertEquals((object) ['name' => 'John Doe', 'age' => 32, 'gender' => 'male', 'city' => 'Vice city'], $res);
        $this->assertNotSame($res, $container->get('doSomething'));
    }

    public function testCallableDefinitionWithDefinitionAndResolveArgumentInMethodWithDefaultValue(): void
    {
        $container = (new DiContainerFactory())->make([
            'doSomething' => ClassWithStaticMethods::class.'::doSomething',
        ]);

        $res = $container->get('doSomething');

        $this->assertEquals((object) ['name' => 'John Doe', 'age' => 32, 'gender' => 'male'], $res);
    }
}
