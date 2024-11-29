<?php

declare(strict_types=1);

namespace Tests\FromDocs\PhpAttribute\Fixtures;

use Kaspi\DiContainer\Interfaces\DiFactoryInterface;
use Psr\Container\ContainerInterface;

class RulesDiFactory implements DiFactoryInterface
{
    public function __construct(
        private RuleA $ruleA,
        private RuleB $ruleB,
    ) {}

    public function __invoke(ContainerInterface $container): array
    {
        return [
            $this->ruleA,
            $this->ruleB,
        ];
    }
}
