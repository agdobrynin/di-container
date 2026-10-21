<?php
#region snippet
// file: /app/src/Services/ReportMaker.php
namespace App\Services;

use App\Interfaces\ReportsStorageInterface;
use App\Storages\ReportsStorage;
use Kaspi\DiContainer\Attributes\Inject;
use Kaspi\DiContainer\Attributes\Parameter;

final class ReportBuilder
{
    public function __construct(
        #[Inject(ReportsStorage::class)]
        public readonly ReportsStorageInterface $storage,
        #[Parameter('params.last_days')]
        public readonly int $lastDays,
    ) {}
}
#endregion snippet
