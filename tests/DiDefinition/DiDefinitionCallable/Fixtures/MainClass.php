<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionCallable\Fixtures;

class MainClass
{
    public function __construct(private string $serviceName) {}

    public function getServiceName(): string
    {
        return $this->serviceName;
    }

    public static function imStatic(string $str): string
    {
        return '❤'.$str;
    }
}
