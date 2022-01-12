<?php

namespace MhsDesign\FusionAfxInEel\ParserExpandEelAfx\Aspects;

use MhsDesign\FusionAfxInEel\ParserExpandEelAfx\FusionPreprocessAfxInEel;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Aop\JoinPointInterface;

/**
 * @Flow\Scope("singleton")
 * @Flow\Aspect
 */
class FusionParserExtractAfxFromEelAspect
{
    /**
     * @Flow\Before("setting(MhsDesign.FusionAfxInEel.aop.enableFusionParserPreprocess) && method(Neos\Fusion\Core\Parser->parse())")
     * @param JoinPointInterface $joinPoint
     */
    public function parserPreprocessExtractAfxOutOfEel(JoinPointInterface $joinPoint)
    {
        $sourceCode = $joinPoint->getMethodArgument('sourceCode');

        if (strpos($sourceCode, 'afx(') === false) {
            return;
        }

        $newSourceCode = FusionPreprocessAfxInEel::extractAfxFromEelAndMakeItAccessibleWithHelperAndPath($sourceCode);

        $joinPoint->setMethodArgument('sourceCode', $newSourceCode);
    }
}
