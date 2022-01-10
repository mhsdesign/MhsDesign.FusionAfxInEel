<?php

namespace MhsDesign\FusionAfxInEel\AfxContent;

use MhsDesign\FusionAfxInEel\RuntimePath\RuntimePath;
use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Fusion\Core\Runtime;

class AfxContentRenderer implements ProtectedContextAwareInterface
{
    protected array $context;
    private Runtime $runtime;
    private string $fusionPath;

    public function __construct(Runtime $runtime, string $fusionPath)
    {
        $this->runtime = $runtime;
        $this->fusionPath = $fusionPath;
    }

    public function withContext(array $context): self
    {
        $this->context = $context;
        return $this;
    }

    public function __toString()
    {
        $needToPop = false;
        if (isset($this->context)) {
            $this->runtime->pushContextArray($this->context);
            $needToPop = true;
        }

        $value = $this->runtime->evaluate($this->fusionPath);

        if ($needToPop) {
            $this->runtime->popContext();
        }

        return $value;
    }

    public function allowsCallOfMethod($methodName)
    {
        return true;
    }
}
