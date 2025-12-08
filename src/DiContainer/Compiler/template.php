use Psr\Container\ContainerInterface;
use Kaspi\DiContainer\Exception\{CallCircularDependencyException, NotFoundException};
use \Kaspi\DiContainer\DiContainer;
use \Kaspi\DiContainer\Interfaces\DiContainerInterface;

use function array_keys;

class <?php print $this->containerClass?> implements \Psr\Container\ContainerInterface
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
        if (false !== $this->containerMap($id)) {
            if (array_key_exist($id, $this->singletonServices)) {
                return $this->singletonServices[$id];
            }

            try {
                if (isset($this->resolvingContainerIds[$id])) {
                    throw new CallCircularDependencyException(callIds: array_keys($this->resolvingContainerIds)+[$id => true]);
                }

                $this->resolvingContainerIds[$id] = true;

                return $this->containerMap($id);
            } finally {
                unset($this->resolvingContainerIds[$id]);
            }
        }

        throw new NotFoundException(id: $id);
    }

    public function has(string $id): bool
    {
        return false !== $this->containerMap($id);
    }

    private function containerMap(string $id): false|string
    {
        return match($id)
        {
<?php foreach ($this->mapContainerIdToMethod as $id => ['method' => $method]) {
            print sprintf('%s => %s,', var_export($id, true), var_export($method, true)).PHP_EOL;
            } ?>
            default => false,
        };
    }
<?php foreach ($this->mapContainerIdToMethod as $id => ['method' => $method, 'compiledEntry' => $compiledEntry]) {

    print sprintf('private %s(): %s
    {
        %s
    }', $method, $compiledEntry->getReturnType(), $compiledEntry->getExpression());
} ?>
}
