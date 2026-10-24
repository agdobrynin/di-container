<?php
declare(strict_types=1);

// file: /app/src/Interfaces/ReportsStorageInterface.php // [!code highlight]

namespace App\Interfaces;

interface ReportsStorageInterface
{
    public function save(): void;
}
