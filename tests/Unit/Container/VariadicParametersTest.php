<?php

declare(strict_types=1);

namespace Tests\Unit\Container;

use PHPUnit\Framework\TestCase;
use Tests\Fixtures\Classes\VariadicSimpleArguments;

/**
 * @internal
 */
class VariadicParametersTest extends TestCase
{
    public function testVariadicSimpleParametersInConstructor(): void
    {
        // @todo resolve by argument name, resolve by reference, resolve by string-class (and interface)
        new VariadicSimpleArguments(one: 'end', two: 'first', three: 'second');
    }
}
