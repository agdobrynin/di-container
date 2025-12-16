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
use Kaspi\DiContainer\DiDefinition\DiDefinitionProxyClosure;
use Kaspi\DiContainer\DiDefinition\DiDefinitionValue;
use Kaspi\DiContainer\Exception\DefinitionCompileException;
use Kaspi\DiContainer\Interfaces\Compiler\CompilableDefinitionInterface;
use Kaspi\DiContainer\Interfaces\Compiler\DiDefinitionTransformerInterface;
use Kaspi\DiContainer\Interfaces\DiContainerInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionAutowireInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionCallableInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionFactoryInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionLinkInterface;
use Kaspi\DiContainer\Interfaces\DiDefinition\DiDefinitionTaggedAsInterface;
use Kaspi\DiContainer\Interfaces\Finder\FinderClosureCodeInterface;

use function get_debug_type;
use function sprintf;

final class DiDefinitionTransformer implements DiDefinitionTransformerInterface
{
    public function __construct(private readonly FinderClosureCodeInterface $closureParser) {}

    public function transform(mixed $definition, DiContainerInterface $container, ?Closure $fallback = null): CompilableDefinitionInterface
    {
        if ($definition instanceof DiDefinitionValue) {
            return new ValueEntry($definition->getDefinition());
        }

        if ($definition instanceof DiDefinitionLinkInterface) {
            return new GetEntry($definition);
        }

        if ($definition instanceof DiDefinitionTaggedAsInterface) {
            return new TaggedAsEntry($definition, $container);
        }

        if ($definition instanceof DiDefinitionProxyClosure) {
            return new ProxyClosureEntry($definition, $container);
        }

        if ($definition instanceof DiDefinitionCallableInterface) {
            return new CallableEntry($definition, $container, $this->closureParser, $this);
        }

        if ($definition instanceof DiDefinitionAutowireInterface) {
            return new ObjectEntry($definition, $container, $this);
        }

        if ($definition instanceof DiDefinitionFactoryInterface) {
            return new FactoryEntry($definition, $container, $this);
        }

        if (null !== $fallback) {
            return ($fallback)($definition, $container);
        }

        throw new DefinitionCompileException(sprintf('Unsupported definition type "%s"', get_debug_type($definition)));
    }

    public function getClosureParser(): FinderClosureCodeInterface
    {
        return $this->closureParser;
    }
}
