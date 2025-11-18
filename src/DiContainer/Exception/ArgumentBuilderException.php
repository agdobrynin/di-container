<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Exception;

use Kaspi\DiContainer\Interfaces\Exceptions\ArgumentBuilderExceptionInterface;

class ArgumentBuilderException extends AutowireException implements ArgumentBuilderExceptionInterface {}
