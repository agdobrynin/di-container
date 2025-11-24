<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionAutowire;

use Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire;
use Kaspi\DiContainer\Exception\NotFoundException;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface;
use PHPUnit\Framework\TestCase;
use Tests\DiDefinition\DiDefinitionAutowire\Fixtures\Quux;
use Tests\DiDefinition\DiDefinitionAutowire\Fixtures\RuleBar;
use Tests\DiDefinition\DiDefinitionAutowire\Fixtures\RuleFoo;

use function Kaspi\DiContainer\diGet;

/**
 * @covers \Kaspi\DiContainer\DiDefinition\Arguments\ArgumentBuilder
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionAutowire
 * @covers \Kaspi\DiContainer\DiDefinition\DiDefinitionGet
 * @covers \Kaspi\DiContainer\diGet
 * @covers \Kaspi\DiContainer\functionName
 * @covers \Kaspi\DiContainer\Traits\ContextExceptionTrait
 *
 * @internal
 */
class FailResolveBindArgumentForVariadicTest extends TestCase
{
    public function testResolveVariadicFailAndExceptionMessage(): void
    {
        $this->expectException(DiDefinitionExceptionInterface::class);
        $this->expectExceptionMessageMatches('/Cannot resolve parameter by named argument \$rule_quux in.+Quux::__construct()/');

        $container = $this->createMock(DiContainerInterface::class);
        $container->method('get')
            ->willReturnCallback(static function (string $id) {
                if ('services.rules.bar' === $id) {
                    return new RuleBar();
                }

                if ('services.rules.foo' === $id) {
                    return new RuleFoo();
                }

                throw new NotFoundException();
            })
        ;

        (new DiDefinitionAutowire(Quux::class))
            ->bindArguments(
                diGet('services.rules.bar'),
                rule_foo: diGet('services.rules.foo'),
                rule_quux: diGet('services.rules.quux')
            )
            ->resolve($container)
        ;
    }
}
