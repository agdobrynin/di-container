<?php
declare(strict_types=1);

namespace Tests\FinderClosureCode;

use Kaspi\DiContainer\Finder\FinderClosureCode;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class FInderClosureCodeTest extends TestCase {
    public function testFunction(): void
    {
        $code = (new FinderClosureCode())
            ->getCode(static fn(ContainerInterface $c): \stdClass => $c->get('services.exclude'));

        var_export($code);

        $code = (new FinderClosureCode())
            ->getCode(static function(): FinderClosureCode {
                $someArg = 'aaa';

                return new FinderClosureCode($someArg);
            });

        var_export($code);
    }
}
