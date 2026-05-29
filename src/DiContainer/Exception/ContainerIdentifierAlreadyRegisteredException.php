<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Exception;

use Kaspi\DiContainer\Interfaces\Exceptions\ContainerIdentifierAlreadyRegisteredExceptionInterface;
use Throwable;

use function rtrim;
use function sprintf;
use function var_export;

class ContainerIdentifierAlreadyRegisteredException extends ContainerException implements ContainerIdentifierAlreadyRegisteredExceptionInterface
{
    public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null, string $id = '')
    {
        if ('' !== $id) {
            $message = rtrim(
                sprintf('The container identifier %s already registered in the source. %s', var_export($id, true), $message),
                ' '
            );
        }

        parent::__construct($message, $code, $previous);
    }
}
