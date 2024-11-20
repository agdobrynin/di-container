<?php

declare(strict_types=1);

namespace Tests\Fixtures\Attributes;

use Kaspi\DiContainer\Attributes\InjectContext;
use Kaspi\DiContainer\Attributes\InjectByReference;

class SimpleDb implements SimpleDbInterface
{
    public function __construct(
        #[InjectContext(arguments: ['array' => '@shared-data'])]
        public \ArrayIterator $data,
        #[InjectByReference('config-table-name')]
        public string $tableName,
    ) {}

    public function insert(string $name): string
    {
        return "user {$name} into table {$this->tableName}";
    }

    public function select(string $name): array
    {
        return [
            'name' => $name,
            'table' => $this->tableName,
        ];
    }
}
