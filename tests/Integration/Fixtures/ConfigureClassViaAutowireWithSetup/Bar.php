<?php

declare(strict_types=1);

namespace Tests\Integration\Fixtures\ConfigureClassViaAutowireWithSetup;

use Kaspi\DiContainer\Attributes\Autowire;
use Kaspi\DiContainer\Attributes\SetupImmutable;
use Kaspi\DiContainer\Attributes\Tag;

#[Autowire(
    setups: [
        'withVal' => new SetupImmutable('Lorem ipsum for id "Bar"'),
    ],
    tags: [
        new Tag('tags.one'),
    ]
)]
#[Autowire(
    id: 'services.bar',
    setups: [
        'withVal' => new SetupImmutable('Lorem ipsum for id "services.bar"'),
    ],
    tags: new Tag('tags.two'),
)]
final class Bar
{
    private string $val = '';

    public function __construct(private readonly Baz $baz) {}

    public function withVal(string $val): self
    {
        $this->val = $val;

        return $this;
    }

    public function getVal(): string
    {
        return $this->val;
    }

    public function getBaz(): Baz
    {
        return $this->baz;
    }
}
