<?php

declare(strict_types=1);

namespace Tests\Fixtures\Attributes;

use Kaspi\DiContainer\Attributes\Inject;

class Lorem
{
    public function __construct(
        #[Inject]
        public SimpleDbInterface $simpleDb
    ) {}

    public function doIt(
        Logger $logger,
        #[Inject(id: '@app.defaultName')]
        string $userName
    ): string {
        return \sprintf('I log to [%s] with data [%s]', $logger->file, $this->simpleDb->insert($userName));
    }
}
