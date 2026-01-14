<?php

declare(strict_types=1);

namespace Tests\Compiler;

use Generator;
use Kaspi\DiContainer\Compiler\CompiledEntry;
use Kaspi\DiContainer\Interfaces\Compiler\Exception\DefinitionCompileExceptionInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(CompiledEntry::class)]
class CompiledEntryTest extends TestCase
{
    public function testDefaults(): void
    {
        $ce = new CompiledEntry();

        self::assertEmpty($ce->getExpression());
        self::assertEmpty($ce->getStatements());
        self::assertEquals('$object', $ce->getScopeServiceVar());
        self::assertEquals(['$object'], $ce->getScopeVars());
        self::assertNull($ce->isSingleton());
        self::assertEquals('mixed', $ce->getReturnType());
    }

    #[DataProvider('dataProviderScopeServiceVarInvalid')]
    public function testScopeServiceVarInvalidThoughtConstructor($scopeServiceVar): void
    {
        $this->expectException(DefinitionCompileExceptionInterface::class);

        new CompiledEntry(scopeServiceVar: $scopeServiceVar);
    }

    #[DataProvider('dataProviderScopeServiceVarValid')]
    public function testScopeServiceVarValidThoughtConstructor($scopeServiceVarIn, $expectVar): void
    {
        self::assertEquals(
            $expectVar,
            (new CompiledEntry(scopeServiceVar: $scopeServiceVarIn))->getScopeServiceVar()
        );
    }

    public static function dataProviderScopeServiceVarValid(): Generator
    {
        yield '$post' => ['$post', '$post'];

        yield '$_' => ['$_', '$_'];

        yield 'with numbers' => ['$service1', '$service1'];

        yield 'none ascii' => ['$😀', '$😀'];
    }

    #[DataProvider('dataProviderScopeServiceVarInvalid')]
    public function testScopeVarsInvalidThroughConstructor($var): void
    {
        $this->expectException(DefinitionCompileExceptionInterface::class);

        new CompiledEntry(scopeVars: [$var]);
    }

    public static function dataProviderScopeServiceVarInvalid(): Generator
    {
        yield 'without $' => ['service'];

        yield 'with middle $' => ['ser$vice'];

        yield 'start with number' => ['$1service'];

        yield 'empty string' => [''];

        yield 'spaces string' => [' '];

        yield 'with invalid char "."' => ['$post.id'];
    }

    public function testDoubleScopeVarsAndUniqueScopeServiceVar(): void
    {
        $scopeServiceVar = '$service';
        $scopeVars = [
            '$service',
            '$object',
            '$object',
            '$service1',
        ];

        $ce = new CompiledEntry(scopeServiceVar: $scopeServiceVar, scopeVars: $scopeVars);

        self::assertEquals('$service2', $ce->getScopeServiceVar());
        self::assertEqualsCanonicalizing(['$service', '$service1', '$service2', '$object'], $ce->getScopeVars());
    }

    public function testAddToScopeVars(): void
    {
        $ce = new CompiledEntry(scopeServiceVar: '$service', scopeVars: ['$service', '$service2']);

        $ce->addToScopeVars('$service', '$service1', '$service2', '$service2', '$service3', '$service4');

        self::assertEqualsCanonicalizing(
            ['$service', '$service1', '$service2', '$service3', '$service4'],
            $ce->getScopeVars(),
        );

        self::assertEquals('$service1', $ce->getScopeServiceVar());
    }

    public function testReplaceExpression(): void
    {
        $ce = new CompiledEntry(expression: '"ok"');
        $ce->setExpression('true');

        self::assertEquals('true', $ce->getExpression());
    }

    public function testAddStatements(): void
    {
        $ce = new CompiledEntry();
        $ce->addToStatements('$one = (object)["flag" => false]')
            ->addToStatements('$one->flag = true')
            ->addToStatements('$two = (object)[]', '$two->flag = true')
        ;

        self::assertEqualsCanonicalizing(
            [
                '$one = (object)["flag" => false]',
                '$one->flag = true',
                '$two = (object)[]',
                '$two->flag = true',
            ],
            $ce->getStatements(),
        );
    }

    public function testSetIsSingleton(): void
    {
        $ce = (new CompiledEntry())
            ->setIsSingleton(true)
        ;

        self::assertTrue($ce->isSingleton());
    }

    public function testSetReturnType(): void
    {
        $ce = (new CompiledEntry())
            ->setReturnType('\App\Logger')
        ;

        self::assertEquals('\App\Logger', $ce->getReturnType());
    }
}
