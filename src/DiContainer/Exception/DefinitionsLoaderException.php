<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Exception;

use Exception;
use Kaspi\DiContainer\Interfaces\Exceptions\DefinitionsLoaderExceptionInterface;

class DefinitionsLoaderException extends Exception implements DefinitionsLoaderExceptionInterface {}
