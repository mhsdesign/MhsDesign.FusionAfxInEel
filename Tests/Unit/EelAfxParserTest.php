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
                    '__meta' => [
                        'afxContent' => [
                            0 => [
                                '__objectType' => 'Neos.Fusion:Tag',
                                '__value' => null,
                                '__eelExpression' => null,
                                'tagName' => 'p',
                                'content' => 'foo'
                            ]
                        ]
                    ],
                    '__eelExpression' => 'Mhs.AfxContent.new(mhsRuntimePath, 0, false, null)',
                    '__value' => null,
                    '__objectType' => null,
                ]
            ]
        ];

        yield 'simple fusion' => [
            <<<'Fusion'
            root = Neos.Fusion:Value {
                value = ${afx(<p>foo</p>)}
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
                            '__meta' => [
                                'afxContent' => [
                                    0 => [
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
                            '__eelExpression' => 'Mhs.AfxContent.new(mhsRuntimePath, 0, false, null)',
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
            root = afx`
                {(foo, bar) => afx()}
            `
            Fusion,
            [
                'root' => [
                    '__meta' => [
                        'afxContent' => [
                            0 => ''
                        ]
                    ],
                    '__eelExpression' => '(foo, bar) => Mhs.AfxContent.new(mhsRuntimePath, 0, false, {foo: foo, bar: bar})',
                    '__value' => null,
                    '__objectType' => null
                ]
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
