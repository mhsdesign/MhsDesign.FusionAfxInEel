<?php

namespace MhsDesign\FusionAfxInEel\RuntimePath\Aspects;

use MhsDesign\FusionAfxInEel\RuntimePath\RuntimePath;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Aop\JoinPointInterface;
use Neos\Fusion\Core\Runtime;

/**
 * Imagine @see \Neos\Fusion\Core\Runtime::evaluateEelExpression() would get passed the fusionPath
 * from its only caller @see \Neos\Fusion\Core\Runtime::evaluateExpressionOrValueInternal()
 *
 * And build up a helper context 'mhsRuntimePath' like 'this' with information of the fusionPath and the runtime.
 *
 * @Flow\Scope("singleton")
 * @Flow\Aspect
 */
class RuntimeEelContextRuntimePathAspect
{
    /**
     * @var string
     * @Flow\Introduce("class(Neos\Fusion\Core\Runtime)")
     */
    public $mhsLastKnownFusionPath;

    /**
     * @Flow\Before("method(Neos\Fusion\Core\Runtime->evaluateExpressionOrValueInternal())")
     * @param JoinPointInterface $joinPoint
     */
    public function setLastKnowFusionPath(JoinPointInterface $joinPoint)
    {
        /** @var Runtime $runtime */
        $runtime = $joinPoint->getProxy();
        $fusionPath = $joinPoint->getMethodArgument('fusionPath');
        $runtime->mhsLastKnownFusionPath = $fusionPath;
    }

    /**
     * @Flow\Around("method(Neos\Fusion\Core\Runtime->evaluateEelExpression())")
     * @param JoinPointInterface $joinPoint
     */
    public function pushContextToEelExpression(JoinPointInterface $joinPoint)
    {
        /** @var Runtime $runtime */
        $runtime = $joinPoint->getProxy();
        $fusionPath = $runtime->mhsLastKnownFusionPath;
        $runtime->mhsLastKnownFusionPath = null;

        $runtimePath = new RuntimePath($runtime, $fusionPath);

        $runtime->pushContext('mhsRuntimePath', $runtimePath);

        $value = $joinPoint->getAdviceChain()->proceed($joinPoint);

        $runtime->popContext();

        return $value;
    }
}
