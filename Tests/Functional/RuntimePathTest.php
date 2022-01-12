<?php

namespace MhsDesign\FusionAfxInEel\Tests\Functional;

use MhsDesign\FusionAfxInEel\RuntimePath\RuntimeWithEelRuntimePath;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Fusion\Core\Runtime;
use PHPUnit\Framework\TestCase;
use Neos\Fusion\Core\Parser;

class RuntimePathTest extends TestCase
{
    public function eelCanAccessRuntimePath()
    {
        yield 'runtimePathKnowsItsPath' => [
            'context' => [],
            'fusion' => <<<'Fusion'
                root = ${mhsRuntimePath.fusionPath}
                Fusion,
            'output' => 'root'
        ];

        yield 'runtimePathKnowsItsPath1' => [
            'context' => [],
            'fusion' => <<<'Fusion'
                root = Neos.Fusion:Value {
                    value = ${mhsRuntimePath.fusionPath}
                }
                Fusion,
            'output' => 'root<Neos.Fusion:Value>/value'
        ];


        yield 'runtimePathKnowsItsPath2' => [
            'context' => [],
            'fusion' => <<<'Fusion'
                root = Neos.Fusion:Value {
                    value = ''
                    value.@process.foo = ${mhsRuntimePath.fusionPath}
                }
                Fusion,
            'output' => 'root<Neos.Fusion:Value>/value/__meta/process/foo'
        ];

        yield 'runtimePathKnowsItsPathRenderer' => [
            'context' => [],
            'fusion' => <<<'Fusion'
                root = Neos.Fusion:Renderer {
                    renderPath = '/bar'
                }
                bar = ${mhsRuntimePath.fusionPath}
                Fusion,
            'output' => 'bar'
        ];

        yield 'runtimePathKnowsItsPathPrototype' => [
            'context' => [],
            'fusion' => <<<'Fusion'

                prototype(Foo:Bar) < prototype(Neos.Fusion:Value) {
                    value = ${mhsRuntimePath.fusionPath}
                }
                root = Foo:Bar
                Fusion,
            'output' => 'root<Foo:Bar>/value'
        ];

        yield 'runtimePathKnowsItsPathType' => [
            'context' => [],
            'fusion' => <<<'Fusion'
                root = Neos.Fusion:Renderer {
                    type = 'Neos.Fusion:Value'
                    element.value = ${mhsRuntimePath.fusionPath}
                }
                Fusion,
            'output' => 'root<Neos.Fusion:Renderer>/element<Neos.Fusion:Value>/value'
        ];

        yield 'runtimePathKnowsAboutItsRuntime' => [
            'context' => [],
            'fusion' => <<<'Fusion'
                root = ${Type.className(mhsRuntimePath.runtime)}
                Fusion,
            'output' => RuntimeWithEelRuntimePath::class
        ];
    }

    /**
     * @test
     * @dataProvider eelCanAccessRuntimePath
     */
    public function runtimePathIsInEelAccessible(array $fusionContext, string $fusionCode, $expectedOutput)
    {
        $fusionCode = "include: resource://Neos.Fusion/Private/Fusion/Root.fusion\n" . $fusionCode;

        $runtime = $this->getRuntimeForFusionCode($fusionCode);
        empty($fusionContext) ?: $runtime->pushContextArray($fusionContext);

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
