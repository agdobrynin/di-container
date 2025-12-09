<?php
use Kaspi\DiContainer\Compiler\CompiledEntry;
/** @var array<non-empty-string, array{0: non-empty-string, 1:CompiledEntry}> $mapContainerIdToMethod */
$mapContainerIdToMethod = $this->mapContainerIdToMethod;
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
        $mapMethod = $this->containerMap($id);

        if (false !== $mapMethod) {
            if (array_key_exists($id, $this->singletonServices)) {
                return $this->singletonServices[$id];
            }

            try {
                if (isset($this->resolvingContainerIds[$id])) {
                    throw new CallCircularDependencyException(callIds: array_keys($this->resolvingContainerIds)+[$id => true]);
                }

                $this->resolvingContainerIds[$id] = true;

                return $this->$mapMethod();
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
        return match($id) {
<?php foreach ($this->mapContainerIdToMethod as $id => [$method]) {?>
            <?php echo \var_export($id, true); ?> => <?php echo \var_export($method, true); ?>,
<?php } ?>
            default => false,
        };
    }
<?php foreach ($this->mapContainerIdToMethod as $id => [$method, $compiledEntry]) {?>

    private function <?php echo $method; ?>(): <?php echo $compiledEntry->getReturnType(); ?>

    {
<?php if ('' !== $compiledEntry->getStatements()) {?>
        <?php echo  $compiledEntry->getStatements()?>

<?php } ?>
<?php if ($compiledEntry->isSingleton()) {?>
        return $this->singletonServices[<?php echo var_export($id, true) ?>] = <?php echo $compiledEntry->getExpression().';'; ?>
<?php }else{ ?>
        return <?php echo $compiledEntry->getExpression().';'; ?>
<?php } ?>

    }
<?php } ?>
}
