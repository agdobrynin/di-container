<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpAttribute\Fixtures;

class MyLogger
{
    public function __construct(
        // Контейнер найдёт интерфейс
        // и проверит у него php-атрибут Service.
        public CustomLoggerInterface $customLogger
    ) {}
}
