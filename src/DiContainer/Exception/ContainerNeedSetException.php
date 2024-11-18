<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Exception;

use Kaspi\DiContainer\Interfaces\Exceptions\ContainerNeedSetExceptionInterface;

class ContainerNeedSetException extends \RuntimeException implements ContainerNeedSetExceptionInterface {}
