<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\DiDefinition;

use Kaspi\DiContainer\Exception\DiDefinitionException;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionNoArgumentsInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionParameterRuntimeInterface;
use UnitEnum;

use function rtrim;
use function sprintf;

final class DiDefinitionParameterRuntime extends DiDefinitionParameterWithContextAbstract implements DiDefinitionParameterRuntimeInterface, DiDefinitionNoArgumentsInterface
{
    private readonly string $message;

    public function __construct(
        private readonly string $name = '',
        ?string $message = null,
    ) {
        $this->message = $message ?? 'Did you forget to define it? Define parameter using method DiContainerInterface::parameters()->set().';
    }

    public function getDefinition(): string
    {
        return $this->name;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function resolve(DiContainerInterface $container, mixed $context = null): array|bool|float|int|string|UnitEnum|null
    {
        $parameterName = $this->nameWithContext($context);

        if (!$container->parameters()->has($parameterName)) {
            throw new DiDefinitionException(
                rtrim(
                    sprintf('The container parameter "%s" must be set in the container at runtime. %s', $parameterName, $this->message)
                )
            );
        }

        return $container->parameters()->get($parameterName);
    }
}
