<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Traits;

use Kaspi\DiContainer\Exception\ContainerNeedSetException;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerNeedSetExceptionInterface;
use Psr\Container\ContainerInterface;

trait PsrContainerTrait
{
    protected ContainerInterface $container;

    public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;
    }

    /**
     * @throws ContainerNeedSetExceptionInterface
     */
    public function getContainer(): ContainerInterface
    {
        return $this->container
            ?? throw new ContainerNeedSetException('Need set container implementation. Use method setContainer() in '.__CLASS__.' class.');
    }
}
