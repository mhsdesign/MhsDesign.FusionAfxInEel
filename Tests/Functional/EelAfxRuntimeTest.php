<?php

namespace MhsDesign\FusionAfxInEel\Tests\Functional;

use MhsDesign\FusionAfxInEel\RuntimePath\RuntimeWithEelRuntimePath;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Fusion\Core\Runtime;
use PHPUnit\Framework\TestCase;
use Neos\Fusion\Core\Parser;

class EelAfxRuntimeTest extends TestCase
{
    public function fusionProcessVariations()
    {
        $context = [];
        $output = '<p>foo</p>';

        yield 'withProcessDirectlyConnectedToFusionObject' => [
            'context' => $context,
            'fusion' => <<<'Fusion'
                root = Neos.Fusion:Value {
                    value = 'foo'
                    @process.bar = ${afx(<p>{value}</p>)}
                }
                Fusion,
            'output' => $output
        ];

        yield 'withProcessNotDirectlyConnectedToFusionObject' => [
            'context' => $context,
            'fusion' => <<<'Fusion'
                root = Neos.Fusion:Value {
                    value = 'foo'
                    value.@process.bar = ${afx(<p>{value}</p>)}
                }
                Fusion,
            'output' => $output
        ];

        yield 'variation1WithProcessNotDirectlyConnectedToFusionObject' => [
            'context' => $context,
            'fusion' => <<<'Fusion'
                root = Neos.Fusion:Value {
                    value = 'foo'
                    value.@process {
                        bar = ${afx(<p>{value}</p>)}
                    }
                }
                Fusion,
            'output' => $output
        ];

        yield 'variation2withProcessNotDirectlyConnectedToFusionObject' => [
            'context' => $context,
            'fusion' => <<<'Fusion'
                root = Neos.Fusion:Value {
                    value = 'foo'
                    value.@process.bar {
                        expression = ${afx(<p>{value}</p>)}
                    }
                }
                Fusion,
            'output' => $output
        ];
    }

    public function contextVariablesAndEvaluation()
    {
        yield 'passingSingleVarWithoutParensToAfx' => [
            'context' => [],
            'fusion' => <<<'Fusion'
                root = afx`
                <p>
                    {Array.join(Array.map([1, 2, 3], item => afx(
                        <a>{item}</a>
                    )), '')}
                </p>
                `
                Fusion,
            'output' => '<p><a>1</a><a>2</a><a>3</a></p>'
        ];

        yield 'passingSingleVarWithParensToAfx' => [
            'context' => [],
            'fusion' => <<<'Fusion'
                root = afx`
                <p>
                    {Array.join(Array.map([1, 2, 3], (item) => afx(
                        <a>{item}</a>
                    )), '')}
                </p>
                `
                Fusion,
            'output' => '<p><a>1</a><a>2</a><a>3</a></p>'
        ];

        yield 'passingMultipleVarsWithParensToAfx' => [
            'context' => [],
            'fusion' => <<<'Fusion'
                root = afx`
                <p>
                    {Array.join(Array.map([1, 2, 3], (item, index) => afx(
                        <a>{item} {index}</a>
                    )), '')}
                </p>
                `
                Fusion,
            'output' => '<p><a>1 0</a><a>2 1</a><a>3 2</a></p>'
        ];

        yield 'useOverrideClosurePassedVar' => [
            'context' => [],
            'fusion' => <<<'Fusion'
                root = afx`
                <p>
                    {Array.join(Array.map([1, 2, 3],
                        (item, index) => afx(
                            <a>{item} {index}</a>
                        ).use({index: 'foo'})
                    ), '')}
                </p>
                `
                Fusion,
            'output' => '<p><a>1 foo</a><a>2 foo</a><a>3 foo</a></p>'
        ];

        yield 'afx eel use outer context' => [
            'context' => [
                'baz' => 'foo'
            ],
            'fusion' => <<<'Fusion'
                root = afx`
                    <p>
                        {afx(Outer: {baz})}
                    </p>
                `
                Fusion,
            'output' => '<p>Outer: foo</p>'
        ];

        yield 'use or arrow params override outer context' => [
            'context' => [
                'item' => 'baz',
                'index' => 'baz'
            ],
            'fusion' => <<<'Fusion'
                root = afx`
                <p>
                    {Array.join(Array.map([1, 2, 3],
                        (item, index) => afx(
                            <a>{item} {index}</a>
                        ).use({index: 'foo'})
                    ), '')}
                </p>
                `
                Fusion,
            'output' => '<p><a>1 foo</a><a>2 foo</a><a>3 foo</a></p>'
        ];


        /**
         * TODO:
         * is failing of course, since the => is not DIRECTLY in front of afx()
         */
//        yield 'passing vars to afx when not preceded by arrow "=>"' => [
//            'context' => [],
//            'fusion' => <<<'Fusion'
//                root = afx`
//                    {Array.join(Array.map(
//                        ['0', '1'],
//                        value => value == '1'
//                            ? afx(true: {value})
//                            : afx(false: {value})
//                    ), ', ')}
//                `
//                Fusion,
//            'output' => 'false: 0, true: 1'
//        ];

        yield 'passing vars explicit' => [
            'context' => [],
            'fusion' => <<<'Fusion'
                root = afx`
                    {Array.join(Array.map(
                        ['0', '1'],
                        value => value == '1'
                            ? afx(true: {value}).use({value: value})
                            : afx(false: {value}).use({value: value})
                    ), ', ')}
                `
                Fusion,
            'output' => 'false: 0, true: 1'
        ];

        yield 'passing this.path var explicit' => [
            'context' => [],
            'fusion' => <<<'Fusion'

                prototype(Foo:Bar.Computed.State) < prototype(Neos.Fusion:Component) {

                    title = ''
                    type = ''

                    _someTag = ${
                        afx(
                            <Neos.Fusion:Tag
                                tagName={afx.type}
                                content={afx.title}
                            />
                        ).use({
                            afx: {
                                type: this.type,
                                title: this.title
                            }
                        })
                    }

                    renderer = afx`
                        {props.type} {props._someTag}
                    `
                }

                root = Foo:Bar.Computed.State {
                    title = 'foo'
                    type = 'h2'
                }
                Fusion,
            'output' => 'h2 <h2>foo</h2>'
        ];


        yield 'loopWithNoVarsPassedToAfxStillReevaluates' => [
            'context' => [
                'timesCalled' => static function (): string {
                    static $timesCalled;
                    if (isset($timesCalled) === false) {
                        $timesCalled = 0;
                    }
                    return ++$timesCalled;
                }
            ],
            'fusion' => <<<'Fusion'
                root = afx`
                    {Array.join(Array.map(['', '', ''], x => afx(
                        <a>{timesCalled()}</a>
                    )), '')}
                `
                Fusion,
            'output' => '<a>1</a><a>2</a><a>3</a>'
        ];
    }


    public function contextObject()
    {
        yield 'withOutFusionObjectContext' => [
            'context' => [],
            'fusion' => <<<'Fusion'
                root = ${afx(hello)}
                Fusion,
            'output' => 'hello'
        ];

        yield 'sameContextObjectForMultipleAfxInEel' => [
            'context' => [],
            'fusion' => <<<'Fusion'
                root = Neos.Fusion:Join {
                    foo = ${afx(foo)}
                    bar = ${afx(bar)}
                }
                Fusion,
            'output' => 'foobar'
        ];
    }

    public function workingAfxInEel()
    {
        yield 'insideTagContent' => [
            'context' => [],
            'fusion' => <<<'Fusion'
                root = afx`
                    <p>{afx(<span>hello</span>)}</p>
                `
                Fusion,
            'output' => '<p><span>hello</span></p>'
        ];

        yield 'insideAttribute' => [
            'context' => [],
            'fusion' => <<<'Fusion'
                root = afx`
                    <p class={afx(hello)}></p>
                `
                Fusion,
            'output' => '<p class="hello"></p>'
        ];

        yield 'afxDirectlyToEel' => [
            'context' => [],
            'fusion' => <<<'Fusion'
                root = Neos.Fusion:Tag {
                    tagName = 'p'
                    content = afx`{afx(foo)}`
                }
                Fusion,
            'output' => '<p>foo</p>'
        ];

        yield 'afxAsPreprocessor' => [
            'context' => [],
            'fusion' => <<<'Fusion'
                root = afx`
                    <p>
                        <a @process.bar={afx(<Neos.Fusion:Value value={'[wrap' + value + ']'}/>)} ></a>
                    </p>
                `
                Fusion,
            'output' => '<p>[wrap<a></a>]</p>'
        ];

        yield 'multipleAfxInOneEel' => [
            'context' => ['foo' => false],
            'fusion' => <<<'Fusion'
                root = afx`
                    <p>
                        {foo
                            ? afx(<button>foo true</button>)
                            : afx(<a>foo</a><br/><p>false</p>)
                        }
                    </p>
                `
                Fusion,
            'output' => '<p><a>foo</a><br /><p>false</p></p>'
        ];

        yield 'nestedAfxInEel' => [
            'context' => [],
            'fusion' => <<<'Fusion'
                root = afx`
                    <p>
                        {afx(<a>
                            {afx(<b>
                                {afx(<c></c>)}
                            </b>)}
                        </a>)}
                    </p>
                `
                Fusion,
            'output' => '<p><a><b><c></c></b></a></p>'
        ];

        yield 'fusionValueInsteadOfTagAsContextObject' => [
            'context' => [],
            'fusion' => <<<'Fusion'
                root = Neos.Fusion:Value {
                    value = ${afx(<a></a>)}
                }
                Fusion,
            'output' => '<a></a>'
        ];

        yield 'fusionDataStructureInsteadOfTagAsContextObject' => [
            'context' => [],
            'fusion' => <<<'Fusion'
                root = Neos.Fusion:DataStructure {
                    foo = ${afx(<a></a>)}
                }
                Fusion,
            'output' => [
                'foo' => '<a></a>'
            ]
        ];
    }

    /**
     * @test
     * @dataProvider fusionProcessVariations
     * @dataProvider contextVariablesAndEvaluation
     * @dataProvider contextObject
     * @dataProvider workingAfxInEel
     */
    public function afxInEelRendersCorrectly(array $fusionContext, string $fusionCode, $expectedOutput)
    {
        $fusionCode = "include: resource://Neos.Fusion/Private/Fusion/Root.fusion\n" . $fusionCode;

        $runtime = $this->getRuntimeForFusionCode($fusionCode);

        empty($fusionContext) ?: $runtime->pushContextArray($fusionContext);

        $renderedFusion = $runtime->render('root');

        $expectedOutput = is_string($expectedOutput) ? trim($expectedOutput) : $expectedOutput;
        $renderedFusion = is_string($renderedFusion) ? trim($renderedFusion) : $renderedFusion;

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
