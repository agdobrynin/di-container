<?php

declare(strict_types=1);

namespace /* diff name space */ Tests\FinderFullyQualifiedClassName\Fixtures\Success {
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

namespace
# What ?
Tests\FinderFullyQualifiedClassName\Fixtures\Success\Others {
    interface GetTokenInterface
    {
        public function token(): string;
    }
    abstract
    /**
     * Hmmm.
     */
        // hmm2
    /**
     * One.
     */
    class ManyNamespacesAbstract implements GetTokenInterface
    {
        private string $token;

        public function token(): string
        {
            return $this->token;
        }

        abstract function foo(): string;
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
