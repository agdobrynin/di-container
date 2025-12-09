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
        $containerEntry = new CompiledEntry('$this', [], ['$this'], 'self');

        $this->mapContainerIdToMethod = [
            ContainerInterface::class => ['getPsrContainer', $containerEntry],
            DiContainerInterface::class => ['getDiContainer', $containerEntry],
        ];
    }

    public function compile(): void
    {
        $fileOper = new FileOperation($this->containerFile);
        ob_start();

        require __DIR__.'/template.php';
        $content = (string) ob_get_clean();
        $bytes = $fileOper->content($content);

        echo sprintf('Write to file "%s" %d bytes.', $this->containerFile, $bytes);
    }
}
