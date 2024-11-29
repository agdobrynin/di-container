<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpAttribute\Fixtures;

class CustomLogger implements CustomLoggerInterface
{
    public function __construct(
        protected string $file,
    ) {}

    public function loggerFile(): string
    {
        return $this->file;
    }
}
