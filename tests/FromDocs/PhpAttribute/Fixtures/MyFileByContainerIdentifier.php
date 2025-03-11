<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpAttribute\Fixtures;

use Kaspi\DiContainer\Attributes\Inject;
use SplFileInfo;

class MyFileByContainerIdentifier
{
    public function __construct(
        #[Inject('services.file')]
        public SplFileInfo $fileInfo
    ) {}
}
