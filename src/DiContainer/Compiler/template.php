<?php

declare(strict_types=1);
use Kaspi\DiContainer\Compiler\ContainerCompiler;
use Kaspi\DiContainer\Interfaces\DiContainerConfigInterface;

// Template for compiled container.
/** @var ContainerCompiler $this */
echo '<?php';

/** @var DiContainerConfigInterface $config */
$config = $this->diContainerDefinitions->getContainer()->getConfig();
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
use Kaspi\DiContainer\Interfaces\RemovedDefinitionIdsInterface;

final class <?php echo $this->getContainerFQN()->getClass(); ?> extends \Kaspi\DiContainer\DiContainer
{
    public function __construct(
        ?RemovedDefinitionIdsInterface $removedDefinitionIds = null
    )
    {
        parent::__construct(
            removedDefinitionIds: $removedDefinitionIds,
            config: new class implements \Kaspi\DiContainer\Interfaces\DiContainerConfigInterface {
                public function isSingletonServiceDefault(): bool
                {
                    return <?php echo \var_export($config->isSingletonServiceDefault(), true); ?>;
                }

                public function isUseZeroConfigurationDefinition(): bool
                {
                    return <?php echo \var_export($config->isUseZeroConfigurationDefinition(), true); ?>;
                }

                public function isUseAttribute(): bool
                {
                    return <?php echo \var_export($config->isUseAttribute(), true); ?>;
                }
            }
        );
    }

    public function set(string $id, mixed $definition): static
    {
        if (false === $this->containerIdMapMethod($id)) {
            return parent::set($id, $definition);
        }

        throw new ContainerAlreadyRegisteredException(
            sprintf('Definition identifier "%s" already registered in container.', $id)
        );
    }

    public function get(string $id): mixed
    {
        /** @var false|non-empty-string $method */
        $method = $this->containerIdMapMethod($id);

        if (false === $method) {
            return $this->config->isUseZeroConfigurationDefinition()
                ? parent::get($id)
                : throw new NotFoundException(id: $id);
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
<?php
$expressionHasDefault = $config->isUseZeroConfigurationDefinition() ? 'parent::has($id)' : 'false';

if (!$this->compiledEntries->getHasIdentifiers()->valid()) {?>
        return <?php echo $expressionHasDefault; ?>;
<?php } else { ?>
        return match($id) {<?php
    $hasIds = $this->compiledEntries->getHasIdentifiers();
    do {
        $id = $hasIds->current();
        $hasIds->next();
        $isLast = !$hasIds->valid();
        ?>

            <?php echo \var_export($id, true).($isLast ? '=> true,' : ',');
    } while ($hasIds->valid()); ?>

            default => <?php echo $expressionHasDefault; ?>

        };
<?php } ?>
    }

    /**
     * Mapping container identifier to internal method for resolving container entry.
     *
     * @return false|non-empty-string
     */
    private function containerIdMapMethod(string $id): false|string
    {
        return match($id) {
<?php foreach ($this->compiledEntries->getContainerIdentifierMappedMethodResolve() as ['id' => $id, 'serviceMethod' => $serviceMethod]) {?>
            <?php echo \var_export($id, true); ?> => <?php echo \var_export($serviceMethod, true); ?>,
<?php } ?>
            default => false,
        };
    }

<?php foreach ($this->compiledEntries->getCompiledEntries() as ['id' => $id, 'serviceMethod' => $method , 'entry' => $compiledEntry]) {?>

    // container identifier <?php echo \var_export($id, true).PHP_EOL; ?>
    private function <?php echo $method; ?>(): <?php echo $compiledEntry->getReturnType(); ?>

    {
<?php
    // build statements
    $statements = '';

    foreach ($compiledEntry->getStatements() as $statement) {
        $statements .= '        '.$statement.';'.PHP_EOL;
    }

    if (null !== \array_key_last($compiledEntry->getStatements())) {
        $statements .= PHP_EOL;
    }

    if (0 === \strcasecmp($compiledEntry->getReturnType(), 'never')) { ?>
<?php echo $statements; ?>
        <?php echo $compiledEntry->getExpression().';'; ?>
<?php } elseif ($compiledEntry->isSingleton()) {?>
        // ⚠ resolved singleton services are stored in parent class
        if (array_key_exists(<?php echo \var_export($id, true); ?>, $this->resolved)) {
            return $this->resolved[<?php echo \var_export($id, true); ?>];
        }

<?php echo $statements; ?>
        return $this->resolved[<?php echo \var_export($id, true); ?>] = <?php echo $compiledEntry->getExpression().';'; ?>
<?php } else { ?>
<?php echo $statements; ?>
        return <?php echo $compiledEntry->getExpression().';'; ?>
<?php } ?>

    }
<?php } ?>
}
