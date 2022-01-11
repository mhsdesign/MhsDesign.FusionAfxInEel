<?php

namespace MhsDesign\FusionAfxInEel\AfxContent\Helper;

use MhsDesign\FusionAfxInEel\RuntimePath\RuntimePath;
use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Fusion\Core\Runtime;

class AfxContentHelper implements ProtectedContextAwareInterface
{
    protected array $context;
    protected Runtime $runtime;
    protected string $fusionPath;

    public const CHAINABLE_METHODS = [
      'withContext'
    ];

    public static function new(RuntimePath $runtimePath, int $index, bool $isChained)
    {
        $afxContentHelper = new self();
        $afxContentHelper->runtime = $runtimePath->getRuntime();

        $currentPath = $runtimePath->getFusionPath();
        $nestedAfxContent = "$currentPath/__meta/afxContent/$index";

        $afxContentHelper->fusionPath = $nestedAfxContent;

        if ($isChained) {
            return $afxContentHelper;
        }

        return $afxContentHelper->render();
    }

    public function withContext(array $context)
    {
        $this->context = $context;
        return $this->render();
    }

    protected function render()
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
