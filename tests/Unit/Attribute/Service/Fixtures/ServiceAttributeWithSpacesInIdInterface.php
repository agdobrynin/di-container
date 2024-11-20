<?php

declare(strict_types=1);

namespace Tests\Unit\Attribute\Service\Fixtures;

use Kaspi\DiContainer\Attributes\Service;

#[Service('           ')]
interface ServiceAttributeWithSpacesInIdInterface {}
