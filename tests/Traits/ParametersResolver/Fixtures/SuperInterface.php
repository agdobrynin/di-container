<?php

declare(strict_types=1);

namespace Tests\Traits\ParametersResolver\Fixtures;

use Kaspi\DiContainer\Attributes\Service;

#[Service(SuperClass::class)]
interface SuperInterface {}
