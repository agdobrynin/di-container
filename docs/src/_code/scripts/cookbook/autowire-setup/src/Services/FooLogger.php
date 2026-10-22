<?php
// file: /app/src/Services/FooLogger.php
namespace App\Services;

use Psr\Log\LoggerInterface;

final class FooLogger
{
    private LoggerInterface $logger;

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function withLogger(LoggerInterface $logger): static
    {
        $new = clone $this;
        $new->logger = $logger;

        return $new;
    }

    public function getLogger(): ?LoggerInterface
    {
        return $this->logger ?? null;
    }
}
