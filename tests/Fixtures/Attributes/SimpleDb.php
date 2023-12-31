<?php

declare(strict_types=1);

namespace Tests\Fixtures\Attributes;

use Kaspi\DiContainer\Attributes\Inject;

class SimpleDb implements SimpleDbInterface
{
    public function __construct(
        #[Inject(arguments: ['dsn' => 'db_dsn'])]
        public \PDO $pdo,
        #[Inject('config-table-name')]
        public string $tableName,
    ) {}

    public function insert(string $name): string
    {
        return "insert {$name} into table {$this->tableName}";
    }

    public function select(string $name): array
    {
        return [
            'name' => $name,
            'table' => $this->tableName,
        ];
    }
}
