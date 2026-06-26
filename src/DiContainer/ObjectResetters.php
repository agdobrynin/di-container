<?php

declare(strict_types=1);

namespace Kaspi\DiContainer;

use Kaspi\DiContainer\Exception\ResetterException;
use Kaspi\DiContainer\Interfaces\Exceptions\ResetterExceptionInterface;
use Kaspi\DiContainer\Interfaces\ObjectResettersInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

use function get_debug_type;
use function is_callable;
use function is_object;
use function is_string;
use function sprintf;
use function var_export;

final class ObjectResetters implements ObjectResettersInterface
{
    /**
     * The iterator key provides the container identifier,
     *  which is used to retrieve a PHP object from the container
     *  using the `\Psr\Container\ContainerInterface::get()` method.
     *
     * @var array<non-empty-string, callable(object): void|non-empty-string>
     */
    private array $resetters = [];

    public function __construct(private readonly ContainerInterface $container) {}

    public function setup(iterable $resetters): void
    {
        $this->resetters = [];

        foreach ($resetters as $id => $resetter) {
            if (!is_string($id) || '' === $id) {
                throw new ResetterException('The iterator key must be a non-empty string.');
            }

            if (!is_callable($resetter) && !is_string($resetter)) {
                throw new ResetterException(sprintf(
                    'Resetter with the key %s must be is `callable(object $object): void` or non-empty string. Got type: "%s".',
                    var_export($id, true),
                    get_debug_type($resetter),
                ));
            }

            $this->resetters[$id] = $resetter;
        }
    }

    public function resetters(): iterable
    {
        yield from $this->resetters;
    }

    public function reset(): void
    {
        foreach ($this->resetters as $id => $resetter) {
            $this->resetService($id, $resetter);
        }
    }

    /**
     * @param non-empty-string          $id
     * @param callable|non-empty-string $resetter
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ResetterExceptionInterface
     */
    private function resetService(string $id, callable|string $resetter): void
    {
        $object = $this->container->get($id);

        if (!is_object($object)) {
            throw new ResetterException(
                sprintf('Entry with container identifier %s should return type "object". Got: "%s"', var_export($id, true), get_debug_type($object))
            );
        }

        if (is_callable($resetter)) {
            ($resetter)($object);
        } elseif (is_callable($callable = [$object, $resetter])) {
            ($callable)();
        } else {
            throw new ResetterException(
                sprintf('Resetter must be is `callable(object $object): void` or existing public method in class "%s" class. Got type: "%s".', $object::class, get_debug_type($resetter))
            );
        }
    }
}
