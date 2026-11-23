<?php
declare(strict_types=1);

// file: /app/config/report_services.php // [!code highlight]

use App\Services\ReportMaker;
use App\Storages\ReportStorage;

use function Kaspi\DiContainer\diAutowire;

return static function (): Generator {
    yield diAutowire(ReportStorage::class)
        ->bindArguments(
            dir: '/var/data'
        );

    yield diAutowire(ReportMaker::class)
        ->bindArguments(
            lastDays: 10
        );

    // other services

};
