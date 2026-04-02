<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Exception;

use Kaspi\DiContainer\Interfaces\Exceptions\ParameterExceptionInterface;
use Throwable;

use function implode;
use function ltrim;
use function sprintf;

final class ParameterCallCircularException extends ContainerException implements ParameterExceptionInterface
{
    /**
     * @param string[] $names parameter names in circular call
     */
    public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null, array $names = [])
    {
        if ([] !== $names) {
            $message = ltrim(
                sprintf('%s Trying call cyclical parameter name. Call name stack: "%s".', $message, implode('" -> "', $names)),
                ' '
            );
        }

        parent::__construct($message, $code, $previous);
    }
}
