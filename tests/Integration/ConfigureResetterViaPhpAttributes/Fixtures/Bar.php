<?php

declare(strict_types=1);

namespace Tests\Integration\ConfigureResetterViaPhpAttributes\Fixtures;

use Kaspi\DiContainer\Attributes\DiRuntime;

#[DiRuntime(
    resetter: static function (Bar $bar): void {
        $bar->setValEmpty();
        $bar->appendValStr();
    }
)]
final class Bar
{
    public function __construct(private string $val = 'Lorem ipsum bar') {}

    public function getVal(): string
    {
        return $this->val;
    }

    public function setValEmpty(): void
    {
        $this->val = '';
    }

    public function appendValStr(): void
    {
        $this->val .= 'str';
    }
}
