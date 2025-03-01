<?php

declare(strict_types=1);

namespace Tests\FinderClass\Fixtures\Success {
    final class ManyNamespaces
    {
        public function __construct(private string $token) {}

        public function token(): string
        {
            return $this->token;
        }
    }
}

namespace Tests\FinderClass\Fixtures\Success\Others {
    abstract class ManyNamespacesAbstract
    {
        private string $token;

        public function token(): string
        {
            return $this->token;
        }
    }

    final class ManyNamespaces
    {
        public function __construct(private string $token) {}

        public function token(): string
        {
            return $this->token;
        }
    }
}
