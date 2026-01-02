<?php

declare(strict_types=1);
use Kaspi\DiContainer\Compiler\ContainerCompiler;

// Template for compiled container.
/** @var ContainerCompiler $this */
echo '<?php';
?>

declare(strict_types=1);
<?php
if ('' !== $this->getContainerFQN()->getNamespace()) { ?>

namespace <?php echo $this->getContainerFQN()->getNamespace(); ?>;

use function array_keys;
use function array_key_exists;
<?php }?>

use Kaspi\DiContainer\Exception\{
    CallCircularDependencyException,
    ContainerAlreadyRegisteredException,
    NotFoundException,
};

final class <?php echo $this->getContainerFQN()->getClass(); ?> extends \Kaspi\DiContainer\DiContainer
{
    public function set(string $id, mixed $definition): static
    {
        if (false === $this->containerMap($id)) {
            return parent::set($id, $definition);
        }

        throw new ContainerAlreadyRegisteredException(
            sprintf('Definition identifier "%s" already registered in container.', $id)
        );
    }

    public function get(string $id): mixed
    {
        /** @var false|array{0: bool|null, 1: non-empty-string} $containerMap */
        $containerMap = $this->containerMap($id);

        if (false === $containerMap) {
            return $this->config->isUseZeroConfigurationDefinition()
                ? parent::get($id)
                : throw new NotFoundException(id: $id);
        }

        [$isSingleton, $method] = $containerMap;

        if (null === $isSingleton) {
            return $this->$method();
        }

        // ⚠ resolved singleton services store in parent class
        if (array_key_exists($id, $this->resolved)) {
            return $this->resolved[$id];
        }

        try {
            if (isset($this->circularCallWatcher[$id])) {
                throw new CallCircularDependencyException(callIds: [...array_keys($this->circularCallWatcher), $id]);
            }

            $this->circularCallWatcher[$id] = true;

            return $this->$method();
        } finally {
            unset($this->circularCallWatcher[$id]);
        }
    }

    public function has(string $id): bool
    {
        $has = false !== $this->containerMap($id);

        return $this->config->isUseZeroConfigurationDefinition()
            ? $has || parent::has($id)
            : $has;
    }

    /**
    * @return false|array{0: bool|null, 1: non-empty-string}
    */
    private function containerMap(string $id): false|array
    {
        return match($id) {
<?php foreach ($this->mapServiceMethodToContainerId as $method => [$id, $compiledEntry]) {?>
            <?php echo \var_export($id, true); ?> => [<?php echo \var_export($compiledEntry->isSingleton(), true); ?>, <?php echo \var_export($method, true); ?>],
<?php } ?>
            default => false,
        };
    }

<?php foreach ($this->mapServiceMethodToContainerId as $method => [$id, $compiledEntry]) {?>

    // container identifier <?php echo \var_export($id, true).PHP_EOL; ?>
    private function <?php echo $method; ?>(): <?php echo $compiledEntry->getReturnType(); ?>

    {
<?php foreach ($compiledEntry->getStatements() as $statement) {?>
        <?php echo $statement; ?>;

<?php } ?>
<?php if (0 === \strcasecmp($compiledEntry->getReturnType(), 'never')) { ?>
        <?php echo $compiledEntry->getExpression().';'; ?>
<?php } elseif ($compiledEntry->isSingleton()) {?>
        // ⚠ resolved singleton services store in parent class
        return $this->resolved[<?php echo \var_export($id, true); ?>] = <?php echo $compiledEntry->getExpression().';'; ?>

<?php } else { ?>
        return <?php echo $compiledEntry->getExpression().';'; ?>

<?php } ?>

    }
<?php } ?>
}
