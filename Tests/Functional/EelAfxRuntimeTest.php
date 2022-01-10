<?php

namespace MhsDesign\FusionAfxInEel\Tests\Functional;

use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Fusion\Core\Runtime;
use PHPUnit\Framework\TestCase;
use Neos\Fusion\Core\Parser;

class EelAfxRuntimeTest extends TestCase
{

    public function doesNotWorkYet()
    {
        // Without fusion context object there is no way to get paths in eel.
        yield 'withOutThisContext' => [
            'context' => [],
            'fusion' => <<<'Fusion'
                root = ${afx(hello)}
                Fusion,
            'output' => 'hello'
        ];

        /*
        Will get transpiled to: but the fusion parser only cares about 'value'
            @afxContent.0 = afx`foo`
            value = ${Mhs.AfxContent.render(this, 0)}
         */
        yield 'directTranspiledAfxToEelWithoutFurtherPaths' => [
            'context' => [],
            'fusion' => <<<'Fusion'
                root = Neos.Fusion:Tag {
                    tagName = 'p'
                    content = afx`{afx(foo)}`
                }
                Fusion,
            'output' => '<p>foo</p>'
        ];

        /*
        works:
        root = Neos.Fusion:Tag {
            tagName = 'a'
            @process.bar = ${afx(<Neos.Fusion:Value value={'[wrap' + value + ']'}/>)}
        }
        and this doenst:
        root = Neos.Fusion:Tag {
            tagName = 'a'
            content.@process.bar = ${afx(<Neos.Fusion:Value value={'[wrap' + value + ']'}/>)}
        }
         */
        yield 'withProcessNotDirectlyConnectedToFusionObject' => [
            'context' => [],
            'fusion' => <<<'Fusion'
                root = Neos.Fusion:Value {
                    value = 'foo'
                    value.@process.bar = ${afx(<p>{value}</p>)}
                }
                Fusion,
            'output' => '<p>foo</p>'
        ];

        yield 'withProcessNotDirectlyConnectedToFusionObject2' => [
            'context' => [],
            'fusion' => <<<'Fusion'
                root = Neos.Fusion:Value {
                    value = 'foo'
                    value.@process {
                        bar = ${afx(<p>{value}</p>)}
                    }
                }
                Fusion,
            'output' => '<p>foo</p>'
        ];

        yield 'withProcessNotDirectlyConnectedToFusionObject3' => [
            'context' => [],
            'fusion' => <<<'Fusion'
                root = Neos.Fusion:Value {
                    value = 'foo'
                    value.@process.bar {
                        expression = ${afx(<p>{value}</p>)}
                    }
                }
                Fusion,
            'output' => '<p>foo</p>'
        ];

        /*
        No option available. Feature.
         */
        yield 'passingVarsToAfxCallback' => [
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

        // will be barbar because this is the same, and the indexes will be both times 0 since no hash is used.
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

        yield 'withProcessConnectedDirectlyToFusionObject' => [
            'context' => [],
            'fusion' => <<<'Fusion'
                root = Neos.Fusion:Value {
                    value = 'foo'
                    @process.bar = ${afx(<p>{value}</p>)}
                }
                Fusion,
            'output' => '<p>foo</p>'
        ];
    }

    /**
     * @test
     * @dataProvider doesNotWorkYet
     * @dataProvider workingAfxInEel
     */
    public function afxInEelRendersCorrectly(array $fusionContext, string $fusionCode, $expectedOutput)
    {
        $fusionCode = "include: resource://Neos.Fusion/Private/Fusion/Root.fusion\n" . $fusionCode;

        $runtime = $this->getRuntimeForFusionCode($fusionCode);
        $runtime->pushContextArray($fusionContext);

        $renderedFusion = $runtime->render('root');

        $expectedOutput = is_string($expectedOutput) ? trim($expectedOutput) : $expectedOutput;
        $renderedFusion = is_string($renderedFusion) ? trim($renderedFusion) : $renderedFusion;

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
