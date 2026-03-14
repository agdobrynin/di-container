<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Exception;

use Kaspi\DiContainer\Interfaces\Exceptions\NotFoundDefinitionInterface;
use Throwable;

use function ltrim;
use function sprintf;

final class NotFoundDefinition extends DefinitionsLoaderException implements NotFoundDefinitionInterface
{
    /**
     * @param string $id container identifier
     */
    public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null, string $id = '')
    {
        if ('' !== $id) {
            $message = ltrim(
                sprintf('%s Definition not found for "%s" identifier.', $message, $id),
                ' '
            );
        }

        parent::__construct($message, $code, $previous);
    }
}
