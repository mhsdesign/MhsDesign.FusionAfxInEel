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
    protected bool $callable = false;

    public const CHAINABLE_METHODS = [
        'use',
        'withContext',
    ];

    /**
     * entry point in eel to render afx path.
     *
     * @return AfxContentHelper|mixed|string|null
     */
    public static function new(RuntimePath $runtimePath, int $index, bool $isChained, ?array $context)
    {
        $currentPath = $runtimePath->getFusionPath();
        $nestedAfxContentPath = "$currentPath/__meta/afxContent/$index";

        $afxContentHelper = new self();
        $afxContentHelper->callable = true;
        $afxContentHelper->runtime = $runtimePath->getRuntime();
        $afxContentHelper->fusionPath = $nestedAfxContentPath;

        if (isset($context)) {
            $afxContentHelper->context = $context;
        }

        if ($isChained) {
            return $afxContentHelper;
        }

        return $afxContentHelper->render();
    }

    /**
     * alias for @see use
     */
    public function withContext(array $context)
    {
        return $this->use($context);
    }

    /**
     * use extra context for rendering.
     */
    public function use(array $context)
    {
        if (isset($this->context)) {
            $this->context = array_merge($this->context, $context);
        } else {
            $this->context = $context;
        }
        return $this->render();
    }

    protected function render()
    {
        try {

            if ($withContext = (empty($this->context) === false)) {
                $completeNewContext = array_merge(
                    $this->runtime->getCurrentContext(),
                    $this->context
                );
                $this->runtime->pushContextArray($completeNewContext);
            }

            return $this->runtime->evaluate($this->fusionPath);

        } finally {

            if ($withContext) {
                $this->runtime->popContext();
            }

        }
    }

    public function allowsCallOfMethod($methodName)
    {
        // the 'constructor'
        if ($methodName === 'new') {
            return true;
        }

        if ($this->callable === false) {
            return false;
        }

        return in_array($methodName, self::CHAINABLE_METHODS, true);
    }
}
