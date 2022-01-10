<?php

namespace MhsDesign\FusionAfxInEel\AfxContent\Helper;

use MhsDesign\FusionAfxInEel\AfxContent\AfxContentRenderer;
use MhsDesign\FusionAfxInEel\RuntimePath\RuntimePath;
use Neos\Eel\ProtectedContextAwareInterface;

class AfxContentHelper implements ProtectedContextAwareInterface
{
    public function fromRuntimePathAndIndex(RuntimePath $runtimePath, int $index): AfxContentRenderer
    {
        $path = $runtimePath->getFusionPath();

        $nestedAfxContent = "$path/__meta/afxContent/$index";

        return new AfxContentRenderer($runtimePath->getRuntime(), $nestedAfxContent);
    }

    public function allowsCallOfMethod($methodName)
    {
        return true;
    }
}
