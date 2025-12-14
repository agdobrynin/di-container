<?php

declare(strict_types=1);
use Kaspi\DiContainer\Compiler\ContainerCompiler;

/**
 * Template for compiled container.
 */
/** @var ContainerCompiler $this */
echo '<?php';
?>

declare(strict_types=1);

<?php
if ($this->getContainerFQN()->getNamespace()) {
    echo 'namespace '.$this->getContainerFQN()->getNamespace().';'.PHP_EOL;
}?>

use Psr\Container\ContainerInterface;
use Kaspi\DiContainer\Exception\{CallCircularDependencyException, NotFoundException};

use function array_keys;
use function array_key_exists;

class <?php echo $this->getContainerFQN()->getClass(); ?> implements ContainerInterface
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
        return match($id) {
<?php foreach ($this->mapContainerIdToMethod as $id => [$method, $compiledEntry]) {?>
            <?php echo \var_export($id, true); ?> => [<?php echo \var_export($compiledEntry->isSingleton(), true); ?>, <?php echo \var_export($method, true); ?>],
<?php } ?>
            default => false,
        };
    }

<?php foreach ($this->mapContainerIdToMethod as $id => [$method, $compiledEntry]) {?>

    private function <?php echo $method; ?>(): <?php echo $compiledEntry->getReturnType(); ?>

    {
    <?php if ('' !== $compiledEntry->getStatements()) {?>
        <?php echo $compiledEntry->getStatements(); ?>

    <?php } ?>
    return <?php if ($compiledEntry->isSingleton()) {?> $this->singletonServices[<?php echo \var_export($id, true); ?>] = <?php } ?><?php echo $compiledEntry->getExpression().';'; ?>

    }
<?php } ?>
}
