<?php

namespace MhsDesign\FusionAfxInEel\Helper;

use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Fusion\FusionObjects\AbstractFusionObject;

/**
 * a more complex way to write
 * this['__meta/afxContent/$index']
 * but the above actually doesn't work when this is a data structure
 */
class AfxContentHelper implements ProtectedContextAwareInterface
{
    public function render(AbstractFusionObject $fusionObject, int $afxPathIndex)
    {
        $relativePath = '__meta/afxContent/' . $afxPathIndex;
        return $fusionObject->offsetGet($relativePath);
    }

    public function allowsCallOfMethod($methodName)
    {
        return true;
    }
}
