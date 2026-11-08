<?php
declare(strict_types=1);

// file: /app/src/Services/Foo.php //[!code highlight]

namespace App\Services;

final class Foo
{
    private ?Qux $qux = null;

    public function setQux(Qux $qux): void
    {
        $this->qux = $qux;
    }

    public function doReset(): void
    {
        $this->qux = null;
    }

    public function getQux(): ?Qux
    {
        return $this->qux;
    }
}
