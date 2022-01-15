<?php

namespace MhsDesign\FusionAfxInEel\AfxContent\Helper;

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
    public static function new(?Runtime $runtime, string $hash, bool $isChained, ?array $context)
    {
        if ($runtime === null) {
            throw new \Exception("Afx content '$hash' couldn't be rendered, since no runtime is available.");
        }

        $absoluteAfxPath = "__meta/afxContent/$hash";

        $afxContentHelper = new self();
        $afxContentHelper->runtime = $runtime;
        $afxContentHelper->fusionPath = $absoluteAfxPath;

        if ($context !== null) {
            $afxContentHelper->context = $context;
        }

        if ($isChained) {
            return $afxContentHelper;
        }

        return $afxContentHelper->render();
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

    /**
     * alias for @see use
     */
    public function withContext(array $context)
    {
        return $this->use($context);
    }

    protected function render()
    {
        if ($withContext = (empty($this->context) === false)) {
            $completeNewContext = array_merge(
                $this->runtime->getCurrentContext(),
                $this->context
            );
            $this->runtime->pushContextArray($completeNewContext);
        }

        $result = $this->runtime->evaluate($this->fusionPath);

        if ($withContext) {
            $this->runtime->popContext();
        }

        return $result;
    }

    public function allowsCallOfMethod($methodName)
    {
        // the 'constructor'
        if ($methodName === 'new') {
            return true;
        }

        return in_array($methodName, self::CHAINABLE_METHODS, true);
    }
}
