<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Compiler;

use RuntimeException;

use function error_get_last;
use function fclose;
use function fopen;
use function fseek;
use function fwrite;
use function is_resource;
use function sprintf;
use function stream_copy_to_stream;
use function tmpfile;

final class FileOperation
{
    /** @var resource */
    private $tmp;

    /** @var resource */
    private $file;

    public function __construct(private readonly string $fileToCompile)
    {
        $this->tmp = tmpfile();

        if (false === $this->tmp) {
            throw new RuntimeException('Failed to create tmp file.');
        }

        // Try to write into tmp
        if (false === @fwrite($this->tmp, '<?php'.PHP_EOL)) {
            throw new RuntimeException('Cannot write to tmp file: '.(error_get_last()['message'] ?? ''));
        }
    }

    public function __destruct()
    {
        if (is_resource($this->tmp)) {
            fclose($this->tmp);
        }

        if (is_resource($this->file)) {
            fclose($this->file);
        }
    }

    public function content(string $content): int
    {
        if (false === @fwrite($this->tmp, $content)) {
            throw new RuntimeException('Cannot write to tmp file: '.(error_get_last()['message'] ?? ''));
        }

        // create compiled container file.
        if (false === $this->file = @fopen($this->fileToCompile, 'wb+')) {
            throw new RuntimeException(
                sprintf('Cannot create compiled container file: "%s". Caused by: %s', $this->fileToCompile, error_get_last()['message'] ?? '')
            );
        }

        // try copy
        fseek($this->tmp, 0);
        if (false === ($bytes = @stream_copy_to_stream($this->tmp, $this->file))) {
            throw new RuntimeException(
                sprintf('Cannot copy temporary file to "%s". Caused by: %s', $this->fileToCompile, error_get_last()['message'] ?? '')
            );
        }

        return $bytes;
    }
}
