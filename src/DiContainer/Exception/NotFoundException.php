<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Exception;

use Psr\Container\NotFoundExceptionInterface;
use Throwable;

use function ltrim;
use function sprintf;

class NotFoundException extends ContainerException implements NotFoundExceptionInterface
{
    /**
     * @param string $id container identifier
     */
    public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null, string $id = '')
    {
        if ('' !== $id) {
            $message = ltrim(
                sprintf('%s No entry was found for "%s" identifier.', $message, $id),
                ' '
            );
        }

        parent::__construct($message, $code, $previous);
    }
}
