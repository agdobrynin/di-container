<?php

declare(strict_types=1);

namespace Tests\DefinitionsLoader\Fixtures\Import;

final class Two implements TokenInterface
{
    public function __construct(private One $one) {}

    public function getToken(): string
    {
        return $this->one->getToken();
    }
}
