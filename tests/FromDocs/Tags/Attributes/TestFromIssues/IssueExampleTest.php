<?php

declare(strict_types=1);

namespace Tests\FromDocs\Tags\Attributes\TestFromIssues;

use Kaspi\DiContainer\DiContainerBuilder;
use LogicException;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Tests\FromDocs\Tags\Attributes\TestFromIssues\Fixtures\RuleMinLength;
use Tests\FromDocs\Tags\Attributes\TestFromIssues\Fixtures\RuleTaggedByInterface;
use Tests\FromDocs\Tags\Attributes\TestFromIssues\Fixtures\RuleTrim;

use function Kaspi\DiContainer\diAutowire;

/**
 * @internal
 */
#[CoversNothing]
class IssueExampleTest extends TestCase
{
    public function testIssueExample(): void
    {
        $definitions = static function () {
            yield diAutowire(RuleMinLength::class)
                ->bindArguments(min: 6)
            ;

            yield diAutowire(RuleTrim::class);
        };

        $container = (new DiContainerBuilder())
            ->addDefinitions($definitions())
            ->build()
        ;

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Invalid string. Minimal length 6 characters. Got: 0 characters');

        $container->get(RuleTaggedByInterface::class)->validate('          ');
    }
}
