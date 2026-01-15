<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Compiler;

use Kaspi\DiContainer\Interfaces\Compiler\ContainerCompilerInterface;
use Kaspi\DiContainer\Interfaces\Compiler\ContainerCompilerToFileInterface;
use RuntimeException;

use function chmod;
use function error_get_last;
use function file_exists;
use function file_put_contents;
use function is_dir;
use function is_readable;
use function is_writable;
use function rtrim;
use function sprintf;

use const LOCK_EX;

final class ContainerCompilerToFile implements ContainerCompilerToFileInterface
{
    /** @var non-empty-string */
    private string $verifiedCompiledFileName;

    /** @var non-empty-string */
    private string $verifiedOutputDirectory;

    /**
     * @param non-empty-string $outputDirectory directory for compiled container
     */
    public function __construct(
        private readonly string $outputDirectory,
        private readonly ContainerCompilerInterface $compiler,
        private readonly int $permissionCompiledContainerFile = 0666,
        private readonly bool $isExclusiveLockFile = true,
    ) {}

    public function getContainerCompiler(): ContainerCompilerInterface
    {
        return $this->compiler; // @codeCoverageIgnore
    }

    public function compileToFile(bool $rebuild = false): string
    {
        $this->verifiedCompiledFileName ??= rtrim($this->getOutputDirectory(), '/').DIRECTORY_SEPARATOR.$this->compiler->getContainerFQN()->getClass().'.php';

        if (false === $rebuild && file_exists($this->verifiedCompiledFileName)) {
            return $this->verifiedCompiledFileName;
        }

        if (!is_writable($this->outputDirectory)) {
            throw new RuntimeException(
                sprintf('Compiler output directory must be be writable. Got argument "%s".', $this->outputDirectory)
            );
        }

        $content = $this->compiler->compile();

        if (false === @file_put_contents($this->verifiedCompiledFileName, $content, $this->isExclusiveLockFile ? LOCK_EX : 0)) {
            throw $this->fileOperationException(sprintf('Failed to write to "%s"', $this->verifiedCompiledFileName));
        }

        @chmod($this->verifiedCompiledFileName, $this->permissionCompiledContainerFile);

        return $this->verifiedCompiledFileName;
    }

    public function getOutputDirectory(): string
    {
        if (!isset($this->verifiedOutputDirectory)) {
            if (!is_dir($this->outputDirectory)) {
                throw new RuntimeException(
                    sprintf('Compiler output directory from parameter $outputDirectory must be exist. Got argument "%s".', $this->outputDirectory)
                );
            }

            if (!is_readable($this->outputDirectory)) {
                throw new RuntimeException(
                    sprintf('Compiler output directory must be be readable. Got argument "%s".', $this->outputDirectory)
                );
            }

            $this->verifiedOutputDirectory = $this->outputDirectory;
        }

        return $this->verifiedOutputDirectory;
    }

    private function fileOperationException(string $message): RuntimeException
    {
        $internalMessage = isset(error_get_last()['message']) ? ' '.error_get_last()['message'] : '';

        return new RuntimeException($message.$internalMessage);
    }
}
