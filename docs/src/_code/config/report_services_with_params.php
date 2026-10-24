<?php
declare(strict_types=1);

// file: /app/config/report_services_with_params.php // [!code highlight]

use App\Services\ReportMaker;
use App\Storages\ReportStorage;

use function Kaspi\DiContainer\{diAutowire, diParameter};

return static function (): Generator {
    yield diAutowire(ReportStorage::class)
        ->bindArguments(
            dir: diParameter('params.report_output_dir'),
        );

    yield diAutowire(ReportMaker::class)
        ->bindArguments(
            lastDays: diParameter('params.last_days')
        );

    // other services

};
