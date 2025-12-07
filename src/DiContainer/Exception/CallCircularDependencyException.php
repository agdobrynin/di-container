<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Exception;

use Throwable;

use function implode;
use function ltrim;
use function sprintf;

class CallCircularDependencyException extends ContainerException
{
    /**
     * @param string[] $ids container identifiers in circular call
     */
    public function __construct(array $ids = [], string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        if ([] !== $ids) {
            $message = ltrim(
                sprintf('%s Trying call cyclical dependency. Call dependencies: %s.', $message, implode(' -> ', $ids)),
                ' '
            );
        }

        parent::__construct($message, $code, $previous);
    }
}
