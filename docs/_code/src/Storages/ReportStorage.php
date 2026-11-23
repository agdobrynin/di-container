<?php
declare(strict_types=1);

// file: /app/src/Storages/ReportStorage.php // [!code highlight]

namespace App\Storages;

final class ReportStorage
{
    public function __construct(
        public readonly string $dir
    ) {}
}
