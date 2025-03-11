<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Exception;

use Kaspi\DiContainer\Interfaces\Exceptions\ContainerNeedSetExceptionInterface;
use RuntimeException;

class ContainerNeedSetException extends RuntimeException implements ContainerNeedSetExceptionInterface {}
