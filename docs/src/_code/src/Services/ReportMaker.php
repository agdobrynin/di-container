<?php
declare(strict_types=1);

// file: /app/src/Services/ReportMaker.php // [!code highlight]

namespace App\Services;

use App\Storages\ReportStorage;

final class ReportMaker
{
    public function __construct(
        public readonly ReportStorage $storage,
        public readonly int $lastDays,
    ) {}
}
