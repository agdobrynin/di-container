<?php

declare(strict_types=1);

namespace Tests\Unit\Container;

use Kaspi\DiContainer\DiContainerFactory;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\Classes\ClassWithStaticMethods;

/**
 * @covers \Kaspi\DiContainer\DiContainer
 * @covers \Kaspi\DiContainer\DiContainerConfig
 * @covers \Kaspi\DiContainer\DiContainerFactory
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionCallable
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionSimple
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
            'welcome_message' => [
                ClassWithStaticMethods::class.'::langWelcomeMessage',
                DiContainerInterface::ARGUMENTS => [
                    'dict' => '@dictionary_welcome_message',
                    'lang' => 'fr',
                ],
            ],
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
            'welcome_message' => [
                ClassWithStaticMethods::class.'::langWelcomeMessage',
                DiContainerInterface::SINGLETON => true,
                DiContainerInterface::ARGUMENTS => [
                    'dict' => '@dictionary_welcome_message',
                    'lang' => 'ru',
                ],
            ],
        ]);

        $res = $container->get('welcome_message');

        $this->assertEquals('добро пожаловать на сайт', $res);
    }

    public function testCallableDefinitionWithNullDefinition(): void
    {
        $container = (new DiContainerFactory())->make();
        $res = $container->get(ClassWithStaticMethods::class.'::doSomething');

        $this->assertEquals((object) ['name' => 'John Doe', 'age' => 32, 'gender' => 'male'], $res);
        $this->assertNotSame($res, $container->get(ClassWithStaticMethods::class.'::doSomething'));
    }
}
