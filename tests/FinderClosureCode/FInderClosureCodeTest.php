<?php
declare(strict_types=1);

namespace Tests\FinderClosureCode;

use Kaspi\DiContainer\Finder\FinderClosureCode;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionAutowireInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use SplFileObject;

class FInderClosureCodeTest extends TestCase {
    public function testFunction(): void
    {
        $code = (new FinderClosureCode())
            ->getCode(static fn(ContainerInterface $c): FinderClosureCode|DiDefinitionAutowireInterface =>
                $c->has('services.exclude')
                    ? $c->get('services.exclude')
                    : new FinderClosureCode()
            );

        var_export($code);

        $code = (new FinderClosureCode())
            ->getCode(static function(string $filename): FinderClosureCode {
                $someArg = new SplFileObject($filename);

                return new FinderClosureCode($someArg);
            });

        var_export($code);
    }
}
