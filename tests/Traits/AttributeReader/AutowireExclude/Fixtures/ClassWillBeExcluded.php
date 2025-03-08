<?php

declare(strict_types=1);

namespace Tests\Traits\AttributeReader\AutowireExclude\Fixtures;

use Kaspi\DiContainer\Attributes\AutowireExclude;

#[AutowireExclude]
final class ClassWillBeExcluded {}
