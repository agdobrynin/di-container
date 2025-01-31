<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Traits;

use Kaspi\DiContainer\Exception\ContainerNeedSetException;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\ContainerNeedSetExceptionInterface;

trait DiContainerTrait
{
    protected DiContainerInterface $container;

    /**
     * @phan-suppress PhanTypeMismatchReturn
     */
    public function setContainer(DiContainerInterface $container): static
    {
        $this->container = $container;

        return $this;
    }

    /**
     * @throws ContainerNeedSetExceptionInterface
     */
    public function getContainer(): DiContainerInterface
    {
        if (!isset($this->container)) {
            throw new ContainerNeedSetException(
                \sprintf('Need set container implementation. Use method setContainer() in %s class.', __CLASS__)
            );
        }

        return $this->container;
    }
}
