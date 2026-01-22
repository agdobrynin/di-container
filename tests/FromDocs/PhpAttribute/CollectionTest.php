<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpAttribute;

use Kaspi\DiContainer\DiContainerBuilder;
use Kaspi\DiContainer\DiContainerConfig;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Tests\FromDocs\PhpAttribute\Fixtures\IterableArg;
use Tests\FromDocs\PhpAttribute\Fixtures\RuleA;
use Tests\FromDocs\PhpAttribute\Fixtures\RuleB;
use Tests\FromDocs\PhpAttribute\Fixtures\RuleInterface;

use function func_get_args;
use function Kaspi\DiContainer\diCallable;

/**
 * @internal
 */
#[CoversNothing]
class CollectionTest extends TestCase
{
    public function testCollection(): void
    {
        $definitions = static function () {
            yield 'services.rule-list' => diCallable(
                definition: static fn (RuleA $a, RuleB $b) => func_get_args(),
                isSingleton: true
            );
        };

        $container = (new DiContainerBuilder(
            containerConfig: new DiContainerConfig(useAttribute: true)
        ))
            ->addDefinitions($definitions())
            ->build()
        ;

        $class = $container->get(IterableArg::class);

        foreach ($class->getValues() as $item) {
            self::assertInstanceOf(RuleInterface::class, $item);
            self::assertContains($item::class, [RuleA::class, RuleB::class]);
        }
    }
}
