<?php

namespace MhsDesign\FusionAfxInEel\Tests\Unit;

use Neos\Fusion\Core\Parser;
use PHPUnit\Framework\TestCase;

class EelAfxParserTest extends TestCase
{
    /**
     * @test
     */
    public function inside_seemingly_not_nested_path_with_donts()
    {
        $fusion = <<<'Fusion'
        root = Neos.Fusion:Value {
            value = ${afx(<p>foo</p>)}
        }
        Fusion;

        $actualAst = (new Parser())->parse($fusion);

        $expectedAst = [
            'root' => [
                '__objectType' => 'Neos.Fusion:Value',
                '__value' => null,
                '__eelExpression' => null,
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
                'value' => [
                    '__eelExpression' => 'Mhs.AfxContent.render(this, 0)',
                    '__value' => null,
                    '__objectType' => null,
                ]
            ]
        ];

        self::assertSame($expectedAst, $actualAst);
    }


    /**
     * @test
     */
    public function with_dots_nested_path_in_line()
    {
        $fusion = <<<'Fusion'
        root = Neos.Fusion:Value
        root.value = ${afx(<p>foo</p>)}
        Fusion;

        $actualAst = (new Parser())->parse($fusion);

        $expectedAst = [
            'root' => [
                '__objectType' => 'Neos.Fusion:Value',
                '__value' => null,
                '__eelExpression' => null,
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
                'value' => [
                    '__eelExpression' => 'Mhs.AfxContent.render(this, 0)',
                    '__value' => null,
                    '__objectType' => null,
                ]
            ]
        ];

        self::assertSame($expectedAst, $actualAst);
    }
}
