<?php

declare(strict_types=1);

namespace Tests\Fixtures\Classes;

use Kaspi\DiContainer\Interfaces\DiFactoryInterface;
use Psr\Container\ContainerInterface;

class DbDiFactory implements DiFactoryInterface
{
    public function __construct(private FileCache $fileCache) {}

    public function __invoke(ContainerInterface $container): Db
    {
        return new Db(data: ['one', 'two'], cache: $this->fileCache);
    }
}
