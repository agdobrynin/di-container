<?php

declare(strict_types=1);

namespace Kaspi\DiContainer;

use Kaspi\DiContainer\Exception\ContainerException;
use Kaspi\DiContainer\Interfaces\DiContainerCallInterface;
use Kaspi\DiContainer\Interfaces\DiContainerCompilerInterface;
use Kaspi\DiContainer\Interfaces\DiContainerConfigInterface;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;

class DiContainerCompiler extends DiContainer implements DiContainerCompilerInterface
{
    protected bool $isCompiled = false;

    public function __construct(protected string $compiledContainerFile, iterable $definitions = [], ?DiContainerConfigInterface $config = null)
    {
        parent::__construct($definitions, $config);
    }

    public function set(string $id, mixed $definition = null, ?array $arguments = null, ?bool $isSingleton = null): static
    {
        if ($this->isCompiled) {
            throw new ContainerException('Container is already compiled.');
        }
        parent::set($id, $definition, $arguments, $isSingleton);

        return $this;
    }

    public function resource(iterable $resources): DiContainerCompilerInterface
    {
        return $this;
    }

    public function exclude(iterable $excludes): DiContainerCompilerInterface
    {
        return $this;
    }

    public function compile(): DiContainerCallInterface&DiContainerInterface
    {
        if (file_exists($this->compiledContainerFile)) {
            $this->isCompiled = true;

            return require_once $this->compiledContainerFile;
        }

        throw new \LogicException('Compiling is not implemented');
    }
}
