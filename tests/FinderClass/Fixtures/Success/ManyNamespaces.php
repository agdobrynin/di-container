<?php

declare(strict_types=1);

namespace Tests\FinderClass\Fixtures\Success {
    interface WithTokenInterface
    {
        public function token(): string;
    }

    final class ManyNamespaces implements WithTokenInterface
    {
        public function __construct(private string $token) {}

        public function token(): string
        {
            return $this->token;
        }
    }
}

namespace Tests\FinderClass\Fixtures\Success\Others {
    interface GetTokenInterface
    {
        public function token(): string;
    }
    abstract class ManyNamespacesAbstract implements GetTokenInterface
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
