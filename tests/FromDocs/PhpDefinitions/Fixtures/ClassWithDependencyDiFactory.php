<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpDefinitions\Fixtures;

use Kaspi\DiContainer\Interfaces\DiFactoryInterface;
use Psr\Container\ContainerInterface;

class ClassWithDependencyDiFactory implements DiFactoryInterface
{
    public function __invoke(ContainerInterface $container): ClassWithDependency
    {
        return new ClassWithDependency(new \SplFileInfo('file1.txt'));
    }
}
