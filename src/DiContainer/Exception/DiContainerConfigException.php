<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Exception;

use Kaspi\DiContainer\Interfaces\Exceptions\DiContainerConfigExceptionInterface;

class DiContainerConfigException extends \RuntimeException implements DiContainerConfigExceptionInterface {}
