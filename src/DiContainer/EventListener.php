<?php

declare(strict_types=1);

namespace Kaspi\DiContainer;

use Kaspi\DiContainer\Enum\EventNameEnum;
use Kaspi\DiContainer\Interfaces\ResetInterface;

/**
 * @internal
 */
final class EventListener implements ResetInterface
{
    /** @var array<non-empty-string,list<callable>> */
    private array $events;

    /**
     * @param callable():void $listener the invoked event
     */
    public function on(EventNameEnum $eventName, callable $listener): void
    {
        $this->events[$eventName->value][] = $listener;
    }

    /**
     * @param array{array-key, mixed}|array{} $args arguments of the invoked event type
     */
    public function trigger(EventNameEnum $eventName, array $args = []): void
    {
        if (!isset($this->events[$eventName->value])) {
            return;
        }

        foreach ($this->events[$eventName->value] as $listener) {
            ($listener)(...$args);
        }
    }

    public function reset(): void
    {
        unset($this->events);
    }
}
