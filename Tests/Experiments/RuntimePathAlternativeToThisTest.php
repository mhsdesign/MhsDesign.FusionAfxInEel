<?php

namespace MhsDesign\FusionAfxInEel\Tests\Functional;

use MhsDesign\FusionAfxInEel\RuntimePath\RuntimePath;
use MhsDesign\FusionAfxInEel\RuntimePath\RuntimeWithEelRuntimePath;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Fusion\Core\Runtime;
use PHPUnit\Framework\TestCase;
use Neos\Fusion\Core\Parser;

/**
 * problem could be with depending paths stuff? in  @ if or context? i dont know...
 * problem could be with caching?
 *
 */
class RuntimePathAlternativeToThisTest extends TestCase
{
    public function relativeRuntimePaths()
    {
        yield 'access direct sibling without context object' => [
            'context' => [],
            'fusion' => <<<'Fusion'
                foo = "something"
                root = ${relative(mhsRuntimePath, "foo")}
                Fusion,
            'output' => 'something'
        ];

        yield 'access direct siblings from the same parse level.' => [
            'context' => [],
            'fusion' => <<<'Fusion'
                root = 'baz'
                root.@process.0 {
                    stuff1 = 'foo'
                    sutff2 = afx`<p>{value}</p>`
                    expression = ${
                        relative(mhsRuntimePath, "stuff1")
                        + relative(mhsRuntimePath, "sutff2")
                    }
                }
                Fusion,
            'output' => 'foo<p>baz</p>'
        ];

        yield 'depending direct siblings' => [
            'context' => [],
            'fusion' => <<<'Fusion'
                root = ${relative(mhsRuntimePath, "stuff1")}
                stuff1 = ${relative(mhsRuntimePath, "stuff2")}
                stuff2 = ${relative(mhsRuntimePath, "stuff3")}
                stuff3 = "bar"
                Fusion,
            'output' => 'bar'
        ];
    }

    public function eelFunctionRelative(RuntimePath $runtimePath, string $siblingPath)
    {
        $segments = explode('/', $runtimePath->getFusionPath());
        array_pop($segments);
        array_push($segments, $siblingPath);
        $fullSiblingPath = join('/', $segments);
        return $runtimePath->getRuntime()->evaluate($fullSiblingPath);
    }

    /**
     * @test
     * @dataProvider relativeRuntimePaths
     */
    public function relativeRuntimePathsAreEvaluated(array $fusionContext, string $fusionCode, $expectedOutput)
    {
        $fusionCode = "include: resource://Neos.Fusion/Private/Fusion/Root.fusion\n" . $fusionCode;

        $runtime = $this->getRuntimeForFusionCode($fusionCode);
        empty($fusionContext) ?: $runtime->pushContextArray($fusionContext);

        $runtime->pushContext('relative', [self::class, 'eelFunctionRelative']);

        $renderedFusion = $runtime->render('root');

        self::assertSame($expectedOutput, $renderedFusion, 'Rendered Fusion didnt match expected.');
    }

    protected function getRuntimeForFusionCode(string $fusionCode): Runtime
    {
        $controllerContext = $this->getMockBuilder(ControllerContext::class)->disableOriginalConstructor()->getMock();
        $fusionAst = (new Parser())->parse($fusionCode);

        $runtime = new RuntimeWithEelRuntimePath($fusionAst, $controllerContext);
        // TODO: Temp. fix #3548
        $runtime->pushContext('somethingSoContextIsNotEmpty', 'bar');
        return $runtime;
    }
}
