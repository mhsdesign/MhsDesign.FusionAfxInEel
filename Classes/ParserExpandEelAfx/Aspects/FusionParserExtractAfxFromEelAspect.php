<?php

namespace MhsDesign\FusionAfxInEel\ParserExpandEelAfx\Aspects;

use MhsDesign\FusionAfxInEel\ParserExpandEelAfx\FusionPreprocessAfxInEel;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Aop\JoinPointInterface;
use Neos\Fusion\Core\DslFactory;
use Neos\Fusion\Core\Parser;
use Neos\Fusion;
use Neos\Utility\Arrays;
use ReflectionMethod;

/*
Problem:

Before AFX to Fusion
----old----
root = Neos.Fusion:Value {
    value = afx`
        {afx(<p>foo</p>)}
    `
}
----new----
root = Neos.Fusion:Value {
    value = afx`
        {afx(<p>foo</p>)}
    `
}

After Afx to Fusion:

----old----
value = ${afx(<p>foo</p>)}
----new----
value = ${Mhs.AfxContent.new(this, '216f62df557e5cb383c52721a9e0596f', false, null)}
@afxContent.'216f62df557e5cb383c52721a9e0596f' = afx`<p>foo</p>`

^
| @afxContent sits at the root, but the fusion parser only cares about $ast['value']

https://github.com/neos/neos-development-collection/blob/cb8f5784c75a0a7ab67f856bfce25c429c5fec82/Neos.Fusion/Classes/Core/Parser.php#L825

 */


/**
 * @Flow\Scope("singleton")
 * @Flow\Aspect
 */
class FusionParserExtractAfxFromEelAspect
{
    /**
     * @Flow\Inject
     * @var DslFactory
     */
    protected $dslFactory;

    /**
     * @var array
     * @Flow\Introduce("class(Neos\Fusion\Core\Parser)")
     */
    public $mhsAdditionalFusionAfxContentHashes = [];

    /**
     * @Flow\Around("method(Neos\Fusion\Core\Parser->parse())")
     * @param JoinPointInterface $joinPoint
     */
    public function parserPreprocessExtractAfxOutOfEel(JoinPointInterface $joinPoint)
    {
        $sourceCode = $joinPoint->getMethodArgument('sourceCode');

        if (strpos($sourceCode, 'afx(') === false) {
            return $joinPoint->getAdviceChain()->proceed($joinPoint);
        }

        $newSourceCode = FusionPreprocessAfxInEel::extractAfxFromEelAndMakeItAccessibleWithHelperAndPath($sourceCode);

        $joinPoint->setMethodArgument('sourceCode', $newSourceCode);

        /** @var array $fusionAst */
        $fusionAst = $joinPoint->getAdviceChain()->proceed($joinPoint);

        /** @var Parser $parser */
        $proxyParser = $joinPoint->getProxy();
        if (empty($proxyParser->mhsAdditionalFusionAfxContentHashes)) {
            return $fusionAst;
        }
        return Arrays::arrayMergeRecursiveOverrule($fusionAst, $proxyParser->mhsAdditionalFusionAfxContentHashes);
    }

    /**
     * Similar functionality, except for @see saveFusionAfxContentFromDslTranspiledAndParsed
     *
     * @Flow\Before("method(Neos\Fusion\Core\Parser->invokeAndParseDsl())")
     * @param JoinPointInterface $joinPoint
     */
    public function parseDslButRememberAfxContentFromRootLevelToo(JoinPointInterface $joinPoint)
    {
        /** @var Parser $proxyParser */
        $proxyParser = $joinPoint->getProxy();

        $renderCurrentFileAndLineInformation = new ReflectionMethod($proxyParser, 'renderCurrentFileAndLineInformation');
        $renderCurrentFileAndLineInformation->setAccessible(true);

        $identifier = $joinPoint->getMethodArgument('identifier');
        $code = $joinPoint->getMethodArgument('code');

        $dslObject = $this->dslFactory->create($identifier);
        try {
            $transpiledFusion = $dslObject->transpile($code);
        } catch (\Exception $e) {
            // convert all exceptions from dsl transpilation to fusion exception and add file and line info
            throw new Fusion\Exception($e->getMessage() . $renderCurrentFileAndLineInformation->invoke($proxyParser), 1180600696);
        }

        $parser = new Parser();
        $temporaryAst = $parser->parse('value = ' . $transpiledFusion);
        $processedValue = $temporaryAst['value'];

        self::saveFusionAfxContentFromDslTranspiledAndParsed($proxyParser, $temporaryAst);
        return $processedValue;
    }

    protected static function saveFusionAfxContentFromDslTranspiledAndParsed(Parser $proxyParser, $temporaryAst): void
    {
        if (isset($temporaryAst['__meta']['afxContent']) === false) {
            return;
        }
        $fusionAfxContentHashes = [
            '__meta' => [
                'afxContent' => $temporaryAst['__meta']['afxContent']
            ]
        ];

        $proxyParser->mhsAdditionalFusionAfxContentHashes =
            Arrays::arrayMergeRecursiveOverrule($proxyParser->mhsAdditionalFusionAfxContentHashes, $fusionAfxContentHashes);
    }
}
