<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Exception;

use Kaspi\DiContainer\Interfaces\Exceptions\ArgumentBuilderExceptionInterface;

final class ArgumentBuilderException extends AutowireException implements ArgumentBuilderExceptionInterface {}
