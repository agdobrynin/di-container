<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpAttribute\Fixtures;

use Kaspi\DiContainer\Attributes\Inject;

class MyFileByContainerIdentifier
{
    public function __construct(
        #[Inject('services.file')]
        public \SplFileInfo $fileInfo
    ) {}
}
