<?php
declare(strict_types=1);

// file: /app/src/Enums/SetupEnum.php //[!code highlight]

namespace App\Enums;

enum SetupEnum
{
    case LOCAL;
    case STAGES;
    case PROD;
}
