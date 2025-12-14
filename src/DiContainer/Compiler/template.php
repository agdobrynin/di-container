<?php

declare(strict_types=1);

/**
 * Template for compiled container.
 */
?>

declare(strict_types=1);

<?php
if ($this->containerNamespace) {
    echo 'namespace '.$this->containerNamespace.';'.PHP_EOL;
}?>

use Psr\Container\ContainerInterface;
use Kaspi\DiContainer\Exception\{CallCircularDependencyException, NotFoundException};

use function array_keys;
use function array_key_exists;

class <?php echo $this->containerClass; ?> implements ContainerInterface
{
    /**
    * When resolving dependency check circular call.
    * @var array<non-empty-string, true>
    */
    private array $resolvingContainerIds = [];

    /**
    * Resolved services as singleton.
    * @var array<non-empty-string, mixed>
    */
    private array $singletonServices = [];

    public function get(string $id): mixed
    {
        /** @var false|array{0: bool|null, 1: non-empty-string} $containerMap */
        $containerMap = $this->containerMap($id);

        if (false === $containerMap) {
            throw new NotFoundException(id: $id);
        }

        [$isSingleton, $method] = $containerMap;

        if (null === $isSingleton) {
            return $this->$method();
        }

        if (array_key_exists($id, $this->singletonServices)) {
            return $this->singletonServices[$id];
        }

        try {
            if (isset($this->resolvingContainerIds[$id])) {
                throw new CallCircularDependencyException(callIds: array_keys($this->resolvingContainerIds)+[$id => true]);
            }

            $this->resolvingContainerIds[$id] = true;

            return $this->$method();
        } finally {
            unset($this->resolvingContainerIds[$id]);
        }
    }

    public function has(string $id): bool
    {
        return false !== $this->containerMap($id);
    }

    /**
    * @return false|array{0: bool|null, 1: non-empty-string}
    */
    private function containerMap(string $id): false|array
    {
    }
}
