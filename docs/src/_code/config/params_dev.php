<?php
declare(strict_types=1);

// file: /app/config/params_dev.php

use App\Enums\SetupEnum;

return [
    'setupType' => SetupEnum::LOCAL,
    'emails.admin' => 'admin@localhost',
    'emails.manager' => 'manager@localhost',
    'app.debug' => true,
];
