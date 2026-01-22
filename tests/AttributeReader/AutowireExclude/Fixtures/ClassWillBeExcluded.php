<?php

declare(strict_types=1);

namespace Tests\AttributeReader\AutowireExclude\Fixtures;

use Kaspi\DiContainer\Attributes\AutowireExclude;

#[AutowireExclude]
final class ClassWillBeExcluded {}
