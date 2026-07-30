<?php

declare(strict_types=1);

namespace Tests\DiContainer\FindTaggedDefinitions;

use Kaspi\DiContainer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;
use Tests\DiContainer\FindTaggedDefinitions\Fixtures\BarValidator;
use Tests\DiContainer\FindTaggedDefinitions\Fixtures\EmailValidator;
use Tests\DiContainer\FindTaggedDefinitions\Fixtures\FooValidator;
use Tests\DiContainer\FindTaggedDefinitions\Fixtures\PasswordValidator;
use Tests\DiContainer\FindTaggedDefinitions\Fixtures\SimplestValidatorInterface;
use Tests\DiContainer\FindTaggedDefinitions\Fixtures\ValidateRuleInterface;

use function array_keys;
use function Kaspi\DiContainer\diAutowire;
use function Kaspi\DiContainer\diGet;
use function Kaspi\DiContainer\diValue;

/**
 * @internal
 */
#[CoversClass(DiContainer\DiContainer::class)]
#[CoversClass(DiContainer\DiContainerConfig::class)]
#[CoversClass(DiContainer\AttributeReader::class)]
#[CoversClass(DiContainer\Attributes\Autowire::class)]
#[CoversClass(DiContainer\Attributes\Tag::class)]
#[CoversClass(DiContainer\DiDefinition\DiDefinitionAutowire::class)]
#[CoversClass(DiContainer\DiDefinition\DiDefinitionValue::class)]
#[CoversClass(DiContainer\DiDefinition\DiDefinitionGet::class)]
#[CoversClass(DiContainer\Helper::class)]
#[CoversClass(DiContainer\Parameters\ImmediateSourceParameters::class)]
#[CoversClass(DiContainer\SourceDefinitions\AbstractSourceDefinitionsMutable::class)]
#[CoversClass(DiContainer\SourceDefinitions\ImmediateSourceDefinitionsMutable::class)]
#[CoversClass(DiContainer\SourceDefinitions\SourceDefinitionItem::class)]
#[CoversClass(DiContainer\Traits\TagsTrait::class)]
#[CoversClass(DiContainer\Traits\FreezeTrait::class)]
#[CoversFunction('Kaspi\DiContainer\diAutowire')]
#[CoversFunction('Kaspi\DiContainer\diValue')]
#[CoversFunction('Kaspi\DiContainer\diGet')]
class FindTaggedDefinitionsTest extends TestCase
{
    public function testFindTaggedDefinitionsViaInterface(): void
    {
        $definitions = [
            diAutowire(EmailValidator::class),
            diAutowire(FooValidator::class),
            diAutowire(PasswordValidator::class),
            'values.simple_arr' => diValue(['simple' => 'array'])
                ->bindTag('tags.simple_arr'),
            'ref.simple_arr' => diGet('values.simple_arr'),
        ];

        $container = new DiContainer\DiContainer(
            $definitions,
            config: new DiContainer\DiContainerConfig(
                useAttribute: true,
            )
        );

        $taggedAsValidateRuleInterface = [...$container->findTaggedDefinitions(ValidateRuleInterface::class)];

        self::assertSame(
            [EmailValidator::class, PasswordValidator::class],
            array_keys($taggedAsValidateRuleInterface)
        );

        // get from cache of tagged definitions.
        self::assertCount(2, [...$container->findTaggedDefinitions(ValidateRuleInterface::class)]);

        $taggedAsSimplestValidatorInterface = [...$container->findTaggedDefinitions(SimplestValidatorInterface::class)];

        self::assertSame(
            [EmailValidator::class, FooValidator::class, PasswordValidator::class],
            array_keys($taggedAsSimplestValidatorInterface)
        );

        // Set new definition with clearing cache of tagged definitions
        $container->set(BarValidator::class, diAutowire(BarValidator::class));

        // Gets again tagged class via interface
        $taggedAsSimplestValidatorInterface = [...$container->findTaggedDefinitions(SimplestValidatorInterface::class)];
        self::assertSame(
            [EmailValidator::class, FooValidator::class, PasswordValidator::class, BarValidator::class],
            array_keys($taggedAsSimplestValidatorInterface)
        );

        // Finds simplest definitions.
        $taggedAs = [...$container->findTaggedDefinitions('tags.simple_arr')];

        self::assertSame(
            ['values.simple_arr'],
            [...array_keys($taggedAs)]
        );
    }

    public function testSetDefinitionWithInvalidClass(): void
    {
        $definitions = [
            diAutowire(FooValidator::class),
        ];

        $container = new DiContainer\DiContainer(
            $definitions,
            config: new DiContainer\DiContainerConfig(
                useAttribute: true,
            )
        );

        $taggedAs = [...$container->findTaggedDefinitions(SimplestValidatorInterface::class)];

        self::assertSame(
            [FooValidator::class],
            array_keys($taggedAs)
        );

        $container->set(
            NoneExistClass::class,
            diAutowire(NoneExistClass::class)
                ->bindTag('tags.none_exist_class')
        );

        $this->expectException(DiContainer\Interfaces\Exceptions\DiDefinitionExceptionInterface::class);
        $this->expectExceptionMessage('NoneExistClass');

        [...$container->findTaggedDefinitions('tags.none_exist_class')];
    }

    public function testResetCacheOfTaggedDefinitionsForSimplesTags(): void
    {
        $definitions = [
            'var.foo' => diValue('foo var')
                ->bindTag('tags.var'),
            'var.baz' => diValue('baz var')
                ->bindTag('tags.other_var'),
            'var.bar' => diValue('bar var')
                ->bindTag('tags.var'),
        ];

        $container = new DiContainer\DiContainer($definitions);

        self::assertSame(
            ['var.foo', 'var.bar'],
            array_keys([...$container->findTaggedDefinitions('tags.var')])
        );
        // get tagged definitions from cache
        self::assertSame(
            ['var.foo', 'var.bar'],
            array_keys([...$container->findTaggedDefinitions('tags.var')])
        );

        $container->set('var.qux', diValue('qux var')->bindTag('tags.var'));

        self::assertSame(
            ['var.foo', 'var.bar', 'var.qux'],
            array_keys([...$container->findTaggedDefinitions('tags.var')])
        );
    }
}
