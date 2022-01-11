<?php

namespace MhsDesign\FusionAfxInEel\RuntimePath;

use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Fusion\Core\Runtime;

class RuntimePath implements ProtectedContextAwareInterface
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

//    public function relative($path)
//    {
//        $segments = explode('/', $this->fusionPath);
//        array_pop($segments);
//        array_push()
//        $parentPath = join();
//    }

    public function __toString()
    {
        return "RuntimePath at: {$this->fusionPath}";
    }

    public function allowsCallOfMethod($methodName)
    {
        return true;
    }
}
