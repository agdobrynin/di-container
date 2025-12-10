<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Compiler;

use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Psr\Container\ContainerInterface;

use function ob_get_clean;
use function ob_start;
use function sprintf;

final class Compiler
{
    /**
     * @var array<non-empty-string, array{0: non-empty-string, 1:CompiledEntry}>
     */
    private array $mapContainerIdToMethod = [];

    public function __construct(
        private readonly DiContainerInterface $container,
        private readonly string $containerNamespace,
        private readonly string $containerClass,
        private readonly string $containerFile,
    ) {
        // TODO check available namespace, container class name.
        $containerEntry = new CompiledEntry('$this', '', [], true, 'self');

        $this->mapContainerIdToMethod = [
            ContainerInterface::class => ['getPsrContainer', $containerEntry],
            DiContainerInterface::class => ['getDiContainer', $containerEntry],
        ];
    }

    public function compile(): void
    {
        $num = 0;
        foreach($this->container->getDefinitions() as $id => $definition) {
            if (!$definition instanceof CompiledDefinitionInterface) {
                throw new \RuntimeException('Cannot compile definition of type "%s"' . get_debug_type($definition));
            }

            $this->mapContainerIdToMethod[$id] = ['getService'.++$num, $definition->compile('$this', $this->container)];
        }

        $fileOper = new FileOperation($this->containerFile);
        ob_start();

        require __DIR__.'/template.php';
        $content = (string) ob_get_clean();
        $bytes = $fileOper->content($content);

        echo sprintf('Write to file "%s" %d bytes.', $this->containerFile, $bytes);
    }
}
