<?php

declare(strict_types=1);

namespace Tests\DiDefinition\DiDefinitionAutowire\Fixtures;

use Kaspi\DiContainer\Attributes\Autowire;
use Kaspi\DiContainer\Attributes\Setup;

#[Autowire(
    setups: [
        'setVal' => new Setup('Lorem ipsum for service id as "'.FooSetupConfigWithIdEmpty::class.'"'),
    ]
)]
#[Autowire(
    id: 'services.foo',
    setups: [
        'setVal' => new Setup('Lorem ipsum for service id as "services.foo"'),
    ],
)]
final class FooSetupConfigWithIdEmpty
{
    public function __construct(private string $val = '') {}

    public function setVal(string $val): void
    {
        $this->val = $val;
    }

    public function getVal(): string
    {
        return $this->val;
    }
}
