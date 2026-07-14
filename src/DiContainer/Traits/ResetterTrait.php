<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Traits;

trait ResetterTrait
{
    /**
     * @var callable|false|non-empty-string
     */
    private $resetter = false;

    /**
     * Provides a reset mechanism for an object obtained via the container's `get()` method.
     *
     * @return callable(object $object): void|false|non-empty-string
     */
    public function getResetter(): callable|false|string
    {
        return $this->resetter;
    }
}
