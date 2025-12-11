<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Compiler;

use Kaspi\DiContainer\Exception\DiDefinitionCompileException;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionCompileInterface;
use Kaspi\DiContainer\Interfaces\Exceptions\DiDefinitionCompileExceptionInterface;
use Psr\Container\ContainerInterface;

use function get_debug_type;
use function ob_get_clean;
use function ob_start;
use function sprintf;

final class Compiler
{
    /**
     * @var array<non-empty-string, array{0: non-empty-string, 1:CompiledEntry}>
     */
    private array $mapContainerIdToMethod;

    public function __construct(
        private readonly DiContainerInterface $container,
        private readonly string $containerNamespace,
        private readonly string $containerClass,
        private readonly string $compilerDirectory,
        private readonly string $containerFile,
    ) {
        // TODO check available namespace, container class name.
        $containerEntry = new CompiledEntry('$this', '', [], null, 'self');

        $this->mapContainerIdToMethod = [
            ContainerInterface::class => ['getPsrContainer', $containerEntry],
            DiContainerInterface::class => ['getDiContainer', $containerEntry],
        ];
    }

    /**
     * @throws DiDefinitionCompileExceptionInterface
     */
    public function compile(): void
    {
        $num = 0;
        foreach ($this->container->getDefinitions() as $id => $definition) {
            if (!$definition instanceof DiDefinitionCompileInterface) {
                throw new DiDefinitionCompileException('Cannot compile definition of type "%s"'.get_debug_type($definition));
            }

            // TODO how about name generator for method name in container.
            $compiledEntity = $definition->compile('$this', $this->container, '$service');
            $serviceMethod = 'getService'.++$num;

            $this->mapContainerIdToMethod[$id] = [$serviceMethod, $compiledEntity];
        }

        $fileOper = new FileOperation($this->containerFile);
        ob_start();

        require __DIR__.'/template.php';
        $content = (string) ob_get_clean();
        $bytes = $fileOper->content($content);

        echo sprintf('Write to file "%s" %d bytes.', $this->containerFile, $bytes);
    }
}
