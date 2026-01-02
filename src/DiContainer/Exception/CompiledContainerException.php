<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Exception;

use Kaspi\DiContainer\Interfaces\Compiler\Exception\CompiledContainerExceptionInterface;
use RuntimeException;
use Stringable;
use Throwable;

use function get_debug_type;

use const PHP_EOL;

final class CompiledContainerException extends RuntimeException implements Stringable, CompiledContainerExceptionInterface
{
    /**
     * @param list<array{
     *      exceptionType: string,
     *      message: string,
     *      file: string,
     *      line: int,
     *      code: int,
     *      trace_as_string: string
     *  }> $exceptionStack
     */
    public function __construct(string $message, int $code = 0, ?Throwable $previous = null, private readonly array $exceptionStack = [])
    {
        parent::__construct($message, $code, $previous);
    }

    public function __toString(): string
    {
        if ([] === $this->exceptionStack) {
            return parent::__toString();
        }

        $exceptionString = '';

        foreach ($this->exceptionStack as $i => $exception) {
            $next = $i > 0 ? PHP_EOL.PHP_EOL.'Next ' : '';
            $exceptionString .= <<< T
{$next}{$exception['exceptionType']}: {$exception['message']} in {$exception['file']}:{$exception['line']}
Stack trace:
{$exception['trace_as_string']}
T;
        }

        $exceptionString .= PHP_EOL.PHP_EOL.'Next '.get_debug_type($this).': '.$this->getMessage().' in '.$this->getFile().':'.$this->getLine();
        $exceptionString .= PHP_EOL.'Stack trace:'.PHP_EOL.$this->getTraceAsString();

        return $exceptionString;
    }

    public function getExceptionStack(): array
    {
        return $this->exceptionStack;
    }
}
