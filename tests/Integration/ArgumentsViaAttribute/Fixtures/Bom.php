<?php

declare(strict_types=1);

namespace Tests\Integration\ArgumentsViaAttribute\Fixtures;

final class Bom implements QuuuxInterface
{
    public function __construct(public readonly string $param = '') {}
}
