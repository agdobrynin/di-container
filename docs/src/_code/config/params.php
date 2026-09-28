<?php
declare(strict_types=1);

// file: /app/config/params.php

use App\Enums\SetupEnum;

return [
    'setupType' => SetupEnum::PROD,
    'emails.admin' => 'admin@example.com',
    'emails.manager' => 'manager@example.com',
    'app.debug' => false,
];
