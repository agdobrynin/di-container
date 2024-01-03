<?php

declare(strict_types=1);

namespace Tests\Unit\Container;

use Kaspi\DiContainer\DiContainer;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;

/**
 * @internal
 *
 * @coversNothing
 */
class ContainerSymbolsTest extends TestCase
{
    public function testDelimiterSymbolsMustBeDifferent(): void
    {
        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessage('Delimiters symbols must be different');

        new DiContainer(linkContainerSymbol: '.', delimiterLevelSymbol: '.');
    }
}
