<?php
declare(strict_types=1);

// file: /app/src/Interfaces/RuleInterface.php

namespace App\Interfaces;

interface RuleInterface
{
    public function validate(mixed $input): mixed;
}
