<?php
declare(strict_types=1);

// file: /app/src/Services/Bar.php //[!code highlight]

namespace App\Services;

final class Bar
{
    private array $logs = [];
    private ?Qux $qux = null;

    // …

    public function setQux(Qux $qux): void
    {
        $this->qux = $qux;
    }

    public function getQux(): ?Qux
    {
        return $this->qux;
    }

    public function log(string $where, string $message): void
    {
        $this->logs[] = [
            'where' => $where,
            'message' => $message,
        ];
    }

    public function getLogs(): array
    {
        return $this->logs;
    }

    public function doResetQux(): void
    {
        $this->qux = null;
    }

    public function doResetLogs(): void
    {
        $this->logs = [];
    }
}
