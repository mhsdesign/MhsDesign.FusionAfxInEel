<?php

namespace MhsDesign\FusionAfxInEel\RuntimePath;

use Neos\Fusion\Core\Runtime;
use Neos\Fusion\FusionObjects\AbstractFusionObject;


class RuntimeWithEelRuntimePath extends Runtime
{
    protected ?string $mhsLastKnownFusionPath = null;

    /**
     * Empty override, to avoid injection settings of this 3. party package
     */
    public function injectSettings(array $settings)
    {
    }

    /**
     * Inject settings of the Neos.Fusion package, and let the original runtime handle them.
     * Configured via Objects.yaml
     */
    public function injectFusionSettings(array $settings)
    {
        parent::injectSettings($settings);
    }

    /**
     * setLastKnowFusionPath
     */
    public function evaluateExpressionOrValueInternal($fusionPath, $fusionConfiguration, $contextObject)
    {
        $this->mhsLastKnownFusionPath = $fusionPath;
        return parent::evaluateExpressionOrValueInternal($fusionPath, $fusionConfiguration, $contextObject);
    }

    /**
     * pushContextToEelExpression
     */
    public function evaluateEelExpression($expression, AbstractFusionObject $contextObject = null)
    {
        $runtimePath = new RuntimePath($this, $this->mhsLastKnownFusionPath);
        $this->mhsLastKnownFusionPath = null;
        try {
            $this->pushContext('mhsRuntimePath', $runtimePath);
            return parent::evaluateEelExpression($expression, $contextObject);
        } finally {
            $this->popContext();
        }
    }
}
