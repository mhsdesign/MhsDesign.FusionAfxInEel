<?php

namespace MhsDesign\FusionAfxInEel\Tests\Functional;

use MhsDesign\FusionAfxInEel\RuntimePath\RuntimeWithEelRuntimePath;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Fusion\Core\Parser;
use Neos\Fusion\Core\Runtime;
use PHPUnit\Framework\TestCase;


class MoreJsxLikeAfxTest extends TestCase
{
    public function jsxStuff()
    {
        yield 'loop with afx closure' => [
            'context' => [],
            'fusion' => <<<'Fusion'
                root = afx`
                    <p>
                        {loop(
                            [1, 2, 3],
                            (item, index) => afx(Item: {item}, Index: {index})
                        )}
                    </p>
                `
                Fusion,
            'output' => '<p>Item: 1, Index: 0Item: 2, Index: 1Item: 3, Index: 2</p>'
        ];

        yield 'loop with afx closure and fusion objects' => [
            'context' => [],
            'fusion' => <<<'Fusion'
                root = afx`
                    <p>
                        {loop(
                            [1, 2, 3],
                            (v, i) => afx(<Neos.Fusion:Join foo={v} bar={i} @glue="-" />),
                            afx(<br/>)
                        )}
                    </p>
                `
                Fusion,
            'output' => '<p>1-0<br />2-1<br />3-2</p>'
        ];



        yield 'ternary afx in loop with nested afx' => [
            'context' => [],
            'fusion' => <<<'Fusion'
                root = Neos.Fusion:Value
                root.value = afx`
                    {loop(
                        [1, 2, 3],
                        item => item % 2
                            ? afx(<Neos.Fusion:Tag content={afx({item} ist ungerade)} />).use({item: item})
                            : afx(<p>{item} ist gerade</p>).use({item: item})
                    )}
                `
                Fusion,
            'output' => '<div>1 ist ungerade</div><p>2 ist gerade</p><div>3 ist ungerade</div>'
        ];

        yield 'saved closure call' => [
            'context' => [],
            'fusion' => <<<'Fusion'
                root = Neos.Fusion:Component {
                    greet = ${name => afx(
                        <h1>Hello {name}</h1>
                    )}

                    renderer = afx`
                        <div>
                            {call(props.greet, 'Marc Henry')}
                        </div>
                    `
                }
                Fusion,
            'output' => '<div><h1>Hello Marc Henry</h1></div>'
        ];

        yield 'saved closure in loop' => [
            'context' => [],
            'fusion' => <<<'Fusion'
                root = Neos.Fusion:Component {
                    greet = ${
                        name => afx(

                            <h1>Hello {name}</h1>

                        )
                    }

                    persons = ${["Heinz", "Günter"]}

                    renderer = afx`
                        {loop(props.persons, props.greet)}
                    `
                }
                Fusion,
            'output' => '<h1>Hello Heinz</h1><h1>Hello Günter</h1>'
        ];


        yield "lol" => [
            'context' => ['something' => true],
            'fusion' => <<<'Fusion'
                root = afx`
                    Hello <del>JSX</del> AFX!
                    {something
                        ? afx(<Button>true</Button>)
                        : afx(<Button2/>)}
                `
                Fusion,
            'output' => 'Hello <del>JSX</del> AFX!<Button>true</Button>'
        ];
    }

    public function eelFunctionLoop(iterable $array, callable $callback, string $separator = '')
    {
        $result = [];
        foreach ($array as $key => $element) {
            $result[] = $callback($element, $key);
        }
        return join($separator, $result);
    }

    public function eelFunctionCall(callable $callback, ...$args)
    {
        return $callback(...$args);
    }

    /**
     * @test
     * @dataProvider jsxStuff
     */
    public function fusionEvaluatesCorrectly(array $fusionContext, string $fusionCode, $expectedOutput)
    {
        $fusionCode = "include: resource://Neos.Fusion/Private/Fusion/Root.fusion\n" . $fusionCode;

        $runtime = $this->getRuntimeForFusionCode($fusionCode);
        empty($fusionContext) ?: $runtime->pushContextArray($fusionContext);

        // eel functions:
        $runtime->pushContext('loop', [self::class, 'eelFunctionLoop']);
        $runtime->pushContext('call', [self::class, 'eelFunctionCall']);

        $renderedFusion = $runtime->render('root');

        self::assertSame($expectedOutput, $renderedFusion, 'Rendered Fusion didnt match expected.');
    }

    protected function getRuntimeForFusionCode(string $fusionCode): Runtime
    {
        $controllerContext = $this->getMockBuilder(ControllerContext::class)->disableOriginalConstructor()->getMock();
        $fusionAst = (new Parser())->parse($fusionCode);

        $runtime = new Runtime($fusionAst, $controllerContext);
        // TODO: Temp. fix #3548
        $runtime->pushContext('somethingSoContextIsNotEmpty', 'bar');
        return $runtime;
    }
}
