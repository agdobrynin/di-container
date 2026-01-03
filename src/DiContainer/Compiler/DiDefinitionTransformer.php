<?php

declare(strict_types=1);

namespace Kaspi\DiContainer\Compiler;

use Closure;
use Kaspi\DiContainer\Compiler\CompilableDefinition\CallableEntry;
use Kaspi\DiContainer\Compiler\CompilableDefinition\FactoryEntry;
use Kaspi\DiContainer\Compiler\CompilableDefinition\GetEntry;
use Kaspi\DiContainer\Compiler\CompilableDefinition\ObjectEntry;
use Kaspi\DiContainer\Compiler\CompilableDefinition\ProxyClosureEntry;
use Kaspi\DiContainer\Compiler\CompilableDefinition\TaggedAsEntry;
use Kaspi\DiContainer\Compiler\CompilableDefinition\ValueEntry;
use Kaspi\DiContainer\Exception\DefinitionCompileException;
use Kaspi\DiContainer\Interfaces\Compiler\CompilableDefinitionInterface;
use Kaspi\DiContainer\Interfaces\Compiler\DiContainerDefinitionsInterface;
use Kaspi\DiContainer\Interfaces\Compiler\DiDefinitionTransformerInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionAutowireInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionCallableInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionFactoryInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionLinkInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionProxyClosureInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionTaggedAsInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionValueInterface;
use Kaspi\DiContainer\Interfaces\Finder\FinderClosureCodeInterface;

use function get_debug_type;
use function sprintf;

final class DiDefinitionTransformer implements DiDefinitionTransformerInterface
{
    public function __construct(private readonly FinderClosureCodeInterface $closureParser) {}

    public function transform(mixed $definition, DiContainerDefinitionsInterface $diContainerDefinitions, ?Closure $fallback = null): CompilableDefinitionInterface
    {
        if ($definition instanceof DiDefinitionValueInterface) {
            return new ValueEntry($definition->getDefinition());
        }

        if ($definition instanceof DiDefinitionLinkInterface) {
            return new GetEntry($definition, $diContainerDefinitions);
        }

        if ($definition instanceof DiDefinitionTaggedAsInterface) {
            return new TaggedAsEntry($definition, $diContainerDefinitions);
        }

        if ($definition instanceof DiDefinitionProxyClosureInterface) {
            return new ProxyClosureEntry($definition, $diContainerDefinitions);
        }

        if ($definition instanceof DiDefinitionCallableInterface) {
            return new CallableEntry($definition, $diContainerDefinitions, $this);
        }

        if ($definition instanceof DiDefinitionAutowireInterface) {
            return new ObjectEntry($definition, $diContainerDefinitions, $this);
        }

        if ($definition instanceof DiDefinitionFactoryInterface) {
            return new FactoryEntry($definition, $diContainerDefinitions, $this);
        }

        if (null !== $fallback) {
            return ($fallback)($definition, $diContainerDefinitions);
        }

        throw new DefinitionCompileException(sprintf('Unsupported definition type "%s"', get_debug_type($definition)));
    }

    public function getClosureParser(): FinderClosureCodeInterface
    {
        return $this->closureParser;
    }
}
