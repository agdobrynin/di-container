<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionTaggedAs\Fixtures\Exclude\Definition;

final class TaggedAsCollection
{
    public function __construct(public iterable $items) {}
}
