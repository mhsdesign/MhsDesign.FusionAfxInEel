<?php

namespace MhsDesign\FusionAfxInEel\Tests\Unit;

use Neos\Fusion\Core\Parser;
use PHPUnit\Framework\TestCase;

class EelAfxParserTest extends TestCase
{

    public function afxInEel()
    {
        $simpleFusion = [
            'root' => [
                '__objectType' => 'Neos.Fusion:Value',
                '__value' => null,
                '__eelExpression' => null,
                'value' => [
                    '__eelExpression' => "Mhs.AfxContent.new(this.runtime, '216f62df557e5cb383c52721a9e0596f', false, null)",
                    '__value' => null,
                    '__objectType' => null,
                ]
            ],
            '__meta' => [
                'afxContent' => [
                    '216f62df557e5cb383c52721a9e0596f' => [
                        '__objectType' => 'Neos.Fusion:Tag',
                        '__value' => null,
                        '__eelExpression' => null,
                        'tagName' => 'p',
                        'content' => 'foo'
                    ]
                ]
            ],
        ];

        yield 'simple fusion' => [
            <<<'Fusion'
            root = Neos.Fusion:Value {
                value = ${afx(<p>foo</p>)}
            }
            Fusion,
            $simpleFusion
        ];

        yield 'simple fusion afx eel afx' => [
            <<<'Fusion'
            root = Neos.Fusion:Value {
                value = afx`
                    {afx(<p>foo</p>)}
                `
            }
            Fusion,
            $simpleFusion
        ];

        yield 'simple fusion different written' => [
            <<<'Fusion'
            root = Neos.Fusion:Value
            root.value = ${afx(<p>foo</p>)}
            Fusion,
            $simpleFusion
        ];


        $fusionProcess = [
            'root' => [
                '__value' => '',
                '__eelExpression' => null,
                '__objectType' => null,
                '__meta' => [
                    'process' => [
                        [
                            '__eelExpression' => "Mhs.AfxContent.new(this.runtime, '6400517aefaaf51e9c4002c1db447504', false, null)",
                            '__value' => null,
                            '__objectType' => null,
                        ],
                    ],
                ],
            ],
            '__meta' => [
                'afxContent' => [
                    '6400517aefaaf51e9c4002c1db447504' => [
                        '__objectType' => 'Neos.Fusion:Tag',
                        '__value' => null,
                        '__eelExpression' => null,
                        'tagName' => 'p',
                        'content' => [
                            '__eelExpression' => 'value',
                            '__value' => null,
                            '__objectType' => null,
                        ],
                    ],
                ],
            ],
        ];

        yield 'fusion eel afx in process' => [
            <<<'Fusion'
            root = ''
            root.@process.0 = ${afx(<p>{value}</p>)}
            Fusion,
            $fusionProcess
        ];

        yield 'fusion eel afx in process alternate syntax' => [
            <<<'Fusion'
            root = ''
            root {
                @process {
                    0 = ${afx(<p>{value}</p>)}
                }
            }
            Fusion,
            $fusionProcess
        ];


        yield 'eel closure arguments to afx() are detected' => [
            <<<'Fusion'
            root = ${(foo, bar) => afx()}
            Fusion,
            [
                'root' => [
                    '__eelExpression' => "(foo, bar) => Mhs.AfxContent.new(this.runtime, 'd41d8cd98f00b204e9800998ecf8427e', false, {foo: foo, bar: bar})",
                    '__value' => null,
                    '__objectType' => null
                ],
                '__meta' => [
                    'afxContent' => [
                        'd41d8cd98f00b204e9800998ecf8427e' => ''
                    ]
                ],
            ]
        ];
    }

    /**
     * @test
     * @dataProvider afxInEel
     */
    public function fusionParsesToAst(string $fusion, array $expectedAst)
    {
        $actualAst = (new Parser())->parse($fusion);
        self::assertSame($expectedAst, $actualAst);
    }
}
