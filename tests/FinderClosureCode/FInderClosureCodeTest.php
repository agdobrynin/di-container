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
            ->getCode(static function (ContainerInterface $c): \stdClass {
                $s = 'aaaa';
                return $c->get($s);
            });
        \var_dump($code);

        $this->assertEquals('                $s = \'aaaa\';'.\PHP_EOL.'                return $c->get($s);', $code);
    }
}
