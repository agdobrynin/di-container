<?php
#region snippet
// file: /app/config/services.php
use App\Services\ReportMaker;
use App\Storages\ReportStorage;

use function Kaspi\DiContainer\{diAutowire, diGet};

return static function (): Generator {
    yield diAutowire(ReportStorage::class)
        ->bindArguments(
            dir: '/var/data'
        );

    yield diAutowire(ReportMaker::class)
        ->bindArguments(
            storage: diGet(ReportStorage::class)
        );

    // other services

};
#endregion snippet