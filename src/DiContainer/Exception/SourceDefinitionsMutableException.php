<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Exception;

use Kaspi\DiContainer\Interfaces\Exceptions\SourceDefinitionsMutableExceptionInterface;

final class SourceDefinitionsMutableException extends ContainerException implements SourceDefinitionsMutableExceptionInterface {}
