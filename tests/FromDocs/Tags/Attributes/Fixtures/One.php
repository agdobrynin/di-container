<?php

declare(strict_types=1);

namespace Tests\FromDocs\Tags\Attributes\Fixtures;

use Kaspi\DiContainer\Attributes\Tag;

#[Tag(name: 'tags.services.group-one')]
#[Tag(name: 'tags.services.group-two')]
class One {}
