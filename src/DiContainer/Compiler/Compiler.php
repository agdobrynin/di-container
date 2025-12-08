<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Compiler;

use Kaspi\DiContainer\DiContainer;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;

use Psr\Container\ContainerInterface;
use function sprintf;

final class Compiler
{
    /**
     * @var array<non-empty-string, CompiledEntry>
     */
    private array $mapContainerIdToMethod = [];

    public function __construct(
        private readonly DiContainerInterface $container,
        private readonly string               $containerClass,
        private readonly string               $containerFile,
    ) {
        $containerEntry = new CompiledEntry('$this', '', '');

        $this->mapContainerIdToMethod = [
            ContainerInterface::class => $containerEntry,
            DiContainerInterface::class => $containerEntry,
            DiContainer::class => $containerEntry
        ];
    }

    public function compile(): void
    {
        if (false === ($tmp = tmpfile())) {
            throw new \RuntimeException('Failed to create tmp file.');
        }

        // try write to tmp
        if (false === @fwrite($tmp, '<?php' . PHP_EOL)) {
            throw new \RuntimeException('Cannot write to tmp file: '.(error_get_last()['message'] ?? ''));
        }

        ob_start();
        require 'template.php';
        $content = ob_get_contents();
        ob_end_clean();

        // append content
        if (false === @fwrite($tmp, $content)) {
            throw new \RuntimeException('Cannot write to tmp file: '.(error_get_last()['message'] ?? ''));
        }

        // create compiled container file.
        if (false === ($file = @fopen($this->containerFile, 'w+'))) {
            throw new \RuntimeException(
                sprintf('Cannot create compiled container file: "%s". Caused by: %s', $this->containerFile, (error_get_last()['message'] ?? ''))
            );
        }

        // try copy
        fseek($tmp, 0);
        if (false === ($bytes = @stream_copy_to_stream($tmp, $file))) {
            throw new \RuntimeException(
                sprintf('Cannot copy from "%s" to "%s". Caused by: %s', stream_get_meta_data($tmp)['uri'], stream_get_meta_data($file)['uri'], (error_get_last()['message'] ?? ''))
            );
        }

        print sprintf('Write to file "%s" %d bytes.',  stream_get_meta_data($file)['uri'], $bytes);
    }
}
