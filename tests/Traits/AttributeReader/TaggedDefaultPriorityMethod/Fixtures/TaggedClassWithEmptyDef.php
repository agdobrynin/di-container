<?php

declare(strict_types=1);

namespace Tests\Traits\AttributeReader\TaggedDefaultPriorityMethod\Fixtures;

use Kaspi\DiContainer\Attributes\TagDefaultPriorityMethod;

#[TagDefaultPriorityMethod(defaultPriorityMethod: '')]
final class TaggedClassWithEmptyDef {}
