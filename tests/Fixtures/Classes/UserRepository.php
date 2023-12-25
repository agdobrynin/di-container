<?php

declare(strict_types=1);

namespace Tests\Fixtures\Classes;

readonly class UserRepository
{
    public function __construct(public Db $db) {}

    public function all(): string
    {
        return implode(', ', $this->db->all());
    }
}
