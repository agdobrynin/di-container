<?php

declare(strict_types=1);

// Template for compiled container.
/** @var \Kaspi\DiContainer\Compiler\ContainerCompiler $this */
echo '<?php';
$definitions = $this->diContainerDefinitions;
$config = $this->diContainerDefinitions->getContainer()->getConfig();
$compiledEntries = $this->compiledEntries;
$runtimeDefinitions = $this->runtimeDefinitions;
$idsForHasMethod = $this->getForHasMethod();
$containerFQN = $this->getContainerFQN();
?>

declare(strict_types=1);
<?php
if ('' !== $containerFQN->getNamespace()) { ?>

namespace <?php echo $containerFQN->getNamespace(); ?>;

use function array_keys;
use function array_key_exists;
<?php }?>

use Kaspi\DiContainer\Exception\{
    CallCircularDependencyException,
    ContainerAlreadyRegisteredException,
    NotFoundException,
};

final class <?php echo $containerFQN->getClass(); ?> extends \Kaspi\DiContainer\DiContainer
{
    public function __construct(
        private readonly array $runtimeDefinitionIds = [
<?php foreach ($runtimeDefinitions as $id => $definition) { ?>
            <?php echo \sprintf('%s => true', \var_export($id, true)); ?>,
<?php } ?>
        ]
    ) {
        parent::__construct(
<?php if ([] !== $runtimeDefinitions) { ?>
            definitions: (static function (): \Generator {
<?php foreach ($runtimeDefinitions as $id => $definition) { ?>
                <?php echo \sprintf('yield \Kaspi\DiContainer\diRuntime(%s, %s);'.PHP_EOL, \var_export($id, true), \var_export($definition->getMessage(), true)); ?>
<?php } ?>
            })(),
<?php } ?>
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
            },
<?php if ($definitions->getContainer()->getRemovedDefinitionIds()->valid()) { ?>
            removedDefinitionIds: (static function (): \Generator {
<?php foreach ($definitions->getContainer()->getRemovedDefinitionIds() as $id => $v) {?>
                <?php echo \sprintf('yield %s => true;'.PHP_EOL, \var_export($id, true))?>
<?php } ?>
            })()
<?php } ?>
        );
    }

    public function set(string $id, mixed $definition): static
    {
        if (isset($this->runtimeDefinitionIds[$id]) || false === $this->containerIdMapMethod($id)) {
            return parent::set($id, $definition);
        }

        throw new ContainerAlreadyRegisteredException(
            sprintf('Definition identifier "%s" already registered in container.', $id)
        );
    }

    public function get(string $id): mixed
    {
        if (isset($this->runtimeDefinitionIds[$id])) {
            return parent::get($id);
        }

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

if (!$idsForHasMethod->valid()) {?>
        return <?php echo $expressionHasDefault; ?>;
<?php } else { ?>
        return match($id) {<?php
    $hasIds = $idsForHasMethod;
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
<?php foreach ($compiledEntries->getContainerIdentifierMappedMethodResolve() as ['id' => $id, 'serviceMethod' => $serviceMethod]) {?>
            <?php echo \var_export($id, true); ?> => <?php echo \var_export($serviceMethod, true); ?>,
<?php } ?>
            default => false,
        };
    }

<?php foreach ($compiledEntries->getCompiledEntries() as ['id' => $id, 'serviceMethod' => $method , 'entry' => $compiledEntry]) {?>

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
