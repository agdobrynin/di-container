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
     * @param string[] $callIds container identifiers in circular call
     */
    public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null, array $callIds = [])
    {
        if ([] !== $callIds) {
            $message = ltrim(
                sprintf('%s Trying call cyclical dependency. Container\'s identifier call stack: "%s".', $message, implode('" -> "', $callIds)),
                ' '
            );
        }

        parent::__construct($message, $code, $previous);
    }
}
