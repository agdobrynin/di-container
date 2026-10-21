<?php
#region snippet
// file: /app/src/Storages/ReportsStorage.php
namespace App\Storages;

use App\Interfaces\ReportsStorageInterface;
use Kaspi\DiContainer\Attributes\Parameter;

final class ReportsStorage implements ReportsStorageInterface
{
    public function __construct(
        #[Parameter('params.report_output_dir')]
        public readonly string $dir
    ) {}

    public function save(): void
    {
        // …
    }
}
#endregion snippet
