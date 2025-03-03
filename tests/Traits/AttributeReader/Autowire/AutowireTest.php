<?php

declare(strict_types=1);

namespace Tests\Traits\AttributeReader\Autowire;

use Kaspi\DiContainer\Interfaces\Exceptions\AutowireExceptionInterface;
use Kaspi\DiContainer\Traits\AttributeReaderTrait;
use Kaspi\DiContainer\Traits\DiContainerTrait;
use PHPUnit\Framework\TestCase;
use Tests\Traits\AttributeReader\Autowire\Fixtures\FailClass;

/**
 * @covers \Kaspi\DiContainer\Traits\AttributeReaderTrait
 *
 * @internal
 */
class AutowireTest extends TestCase
{
    use AttributeReaderTrait;
    use DiContainerTrait;

    public function testAutowireCannotUseWithAutowireExclude(): void
    {
        $this->expectException(AutowireExceptionInterface::class);
        $this->expectExceptionMessageMatches('/Cannot use together attributes.+Autowire.+AutowireExclude/');

        $this->getAutowireAttribute(new \ReflectionClass(FailClass::class))->valid();
    }
}
