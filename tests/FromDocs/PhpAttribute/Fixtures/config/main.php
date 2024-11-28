<?php

declare(strict_types=1);
use function Kaspi\DiContainer\diAutowire;

return [
    'services.file.prod' => diAutowire(SplFileInfo::class)
        ->addArgument('filename', __DIR__.'/../file1.txt'),
    'services.file.local' => diAutowire(SplFileInfo::class)
        ->addArgument('filename', __DIR__.'/../file2.txt'),
];
