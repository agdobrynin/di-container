<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Exception;

use Kaspi\DiContainer\Interfaces\Exceptions\ParameterNotFoundExceptionInterface;
use Throwable;

use function ltrim;
use function sprintf;

final class ParameterNotFoundException extends NotFoundException implements ParameterNotFoundExceptionInterface
{
    /**
     * @param string $name parameter name
     */
    public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null, string $name = '')
    {
        if ('' !== $name) {
            $message = ltrim(
                sprintf('%s Parameter name "%s" not found.', $message, $name),
                ' '
            );
        }

        parent::__construct($message, $code, $previous);
    }
}
