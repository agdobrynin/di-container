<?php
#region snippet
// file: /app/src/Storages/ReportStorage.php
namespace App\Storages;

final class ReportStorage
{
    public function __construct(
        public readonly string $dir
    ) {}
}
#endregion snippet