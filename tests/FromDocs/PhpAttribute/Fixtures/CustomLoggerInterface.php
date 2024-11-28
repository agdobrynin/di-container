<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpAttribute\Fixtures;

use Kaspi\DiContainer\Attributes\Service;

#[Service(CustomLogger::class)] // класс реализующий данный интерфейс.
interface CustomLoggerInterface
{
    public function loggerFile(): string;
}
