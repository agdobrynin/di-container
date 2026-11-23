<?php
declare(strict_types=1);

// file: /app/src/Services/FooLogger.php [!code highlight]

namespace  App\Services;

final class FooLogger
{
    private array $logs = [];

    // other methods

    public function log(string $message, array $context): void
    {
        $this->logs[] = [
            'message' => $message,
            'context' => $context,
        ];
    }

    public function getLogs(): array
    {
        return $this->logs;
    }
}
