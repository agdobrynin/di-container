<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpAttribute\Fixtures;

use Kaspi\DiContainer\Attributes\Inject;
use SplFileInfo;

class MyFile
{
    public function __construct(
        #[Inject]
        public SplFileInfo $fileInfo
    ) {}
}
