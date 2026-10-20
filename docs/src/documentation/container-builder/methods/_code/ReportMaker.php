<?php
#region snippet
// file: /app/src/Services/ReportMaker.php
namespace App\Services;

use App\Storages\ReportStorage;

final class ReportMaker
{
    public function __construct(
        public readonly ReportStorage $storage
    ) {}
}
#endregion snippet