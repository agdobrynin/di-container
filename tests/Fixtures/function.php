<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use Tests\DiDefinition\DiDefinitionCallable\Fixtures\Two;

function funcServiceTwo(): Two
{
    return new Two('fromFunction');
}
