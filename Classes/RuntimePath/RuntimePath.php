<?php

namespace MhsDesign\FusionAfxInEel\RuntimePath;

use Neos\Fusion\Core\Runtime;

class RuntimePath
{
    private Runtime $runtime;
    private string $fusionPath;

    public function __construct(Runtime $runtime, string $fusionPath)
    {
        $this->runtime = $runtime;
        $this->fusionPath = $fusionPath;
    }

    public function getRuntime()
    {
        return $this->runtime;
    }

    public function getFusionPath()
    {
        return $this->fusionPath;
    }

    public function __toString()
    {
        return "RuntimePath at: {$this->fusionPath}";
    }
}
