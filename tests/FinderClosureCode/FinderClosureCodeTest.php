<?php

declare(strict_types=1);

namespace Tests\FinderClosureCode;

use Kaspi\DiContainer\Finder\FinderClosureCode;
use PHPUnit\Framework\TestCase;

use function var_export;

/**
 * @internal
 */
class FinderClosureCodeTest extends TestCase
{
    public function testFunction(): void
    {
        $services = (require __DIR__.'/Fixture/yield_services.php')();

        $code = (new FinderClosureCode())->getCode($services->current());

        var_export($code);

        //        $services->next();
        //
        //        $code = (new FinderClosureCode())->getCode($services->current());
        //
        //        var_export($code);
    }
}
