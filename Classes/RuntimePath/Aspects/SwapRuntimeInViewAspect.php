<?php

namespace MhsDesign\FusionAfxInEel\RuntimePath\Aspects;

use MhsDesign\FusionAfxInEel\RuntimePath\RuntimeWithEelRuntimePath;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Aop\JoinPointInterface;
use Neos\Fusion\Core\Runtime;
use Neos\Neos\Domain\Service\FusionService;

/**
 * This Aspect will make sure that the vanilla FusionView uses the Custom Runtime.
 *
 * @Flow\Aspect
 * @Flow\Scope("singleton")
 */
class SwapRuntimeInViewAspect
{
    /**
     * @Flow\Around("setting(MhsDesign.FusionAfxInEel.aop.enableCustomRuntimeForFusionService) && method(Neos\Neos\Domain\Service\FusionService->createRuntime())")
     * @param JoinPointInterface $joinPoint
     */
    public function useRuntimeWithEelRuntimePath(JoinPointInterface $joinPoint): Runtime
    {
        $currentSiteNode = $joinPoint->getMethodArgument('currentSiteNode');
        $controllerContext = $joinPoint->getMethodArgument('controllerContext');

        /** @var FusionService $fusionService */
        $fusionService = $joinPoint->getProxy();
        $fusionObjectTree = $fusionService->getMergedFusionObjectTree($currentSiteNode);

        $fusionRuntime = new RuntimeWithEelRuntimePath($fusionObjectTree, $controllerContext);
        return $fusionRuntime;
    }
}
