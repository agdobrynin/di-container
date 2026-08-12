<?php

declare(strict_types=1);

namespace Tests\DefinitionsLoader\DefinitionsConfigurator\Fixures;

final class Maker
{
    public static function managerEmail(): string
    {
        // calculate current manager email!
        return 'manager@example.com';
    }
}
