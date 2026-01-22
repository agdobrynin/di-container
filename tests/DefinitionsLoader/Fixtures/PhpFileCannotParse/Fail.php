<?php

declare(strict_types=1);

namespace Tests\DefinitionsLoader\Fixtures\PhpFileCannotParse;

final class Fail {
    public function __construct(private string src) {}
}
