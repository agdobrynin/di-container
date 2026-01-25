<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Exception;

use Kaspi\DiContainer\Interfaces\Compiler\Exception\CompiledContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Throwable;

use function ltrim;
use function sprintf;

final class CompiledContainerNotFoundException extends CompiledContainerExceptionAbstract implements NotFoundExceptionInterface, CompiledContainerExceptionInterface
{
    public function __construct(string $message, int $code = 0, ?Throwable $previous = null, array $exceptionStack = [], string $id = '')
    {
        if ('' !== $id) {
            $message = ltrim(
                sprintf('%s No entry was found for "%s" identifier.', $message, $id),
                ' '
            );
        }

        parent::__construct($message, $code, $previous, $exceptionStack);
    }
}
