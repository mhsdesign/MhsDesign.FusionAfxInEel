<?php

namespace MhsDesign\FusionAfxInEel\Aspects;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Aop\JoinPointInterface;

/**
 * @Flow\Scope("singleton")
 * @Flow\Aspect
 */
class FusionParserExtractAfxFromEelAspect
{
    protected const PATTERN_AFX_IN_EEL_EXPRESSION = <<<'REGEX'
    /
      afx\(
        (?P<afx>
          (?>
            \( (?P>afx) \)          # match object literal expression recursively
            |[^()"']+	            # simple eel expression without quoted strings
            |"[^"\\]*			    # double quoted strings with possibly escaped double quotes
              (?:
                \\.			# escaped character (quote)
                [^"\\]*		# unrolled loop following Jeffrey E.F. Friedl
              )*"
            |'[^'\\]*			# single quoted strings with possibly escaped single quotes
              (?:
                \\.			# escaped character (quote)
                [^'\\]*		# unrolled loop following Jeffrey E.F. Friedl
              )*'
          )*
        )
      \)
    /x
    REGEX;

    const PATTERN_OBJECT_PATH_EEL_EXPRESSION_ASSIGN = <<<'REGEX'
    /
        (?P<indent>[ \t]*)                             # beginning of line; with numerous whitespace
        (?P<objectPath>                                 # (nested) objectPath
            (?>
                [a-zA-Z0-9.():@_\-]+         # Unquoted key
                |"(?:\\"|[^"])+"            # Double quoted key, supporting more characters like underscore and at sign
                |'(?:\\'|[^'])+'       # Single quoted key, supporting more characters like underscore and at sign
            )+
        )
        [ \t]*
        =
        [ \t]*
        \${(?P<exp>
            (?>
              { (?P>exp) }          # match object literal expression recursively
              |[^{}"']+	            # simple eel expression without quoted strings
              |"[^"\\]*			    # double quoted strings with possibly escaped double quotes
                (?:
                  \\.			# escaped character (quote)
                  [^"\\]*		# unrolled loop following Jeffrey E.F. Friedl
                )*"
              |'[^'\\]*			# single quoted strings with possibly escaped single quotes
                (?:
                  \\.			# escaped character (quote)
                  [^'\\]*		# unrolled loop following Jeffrey E.F. Friedl
                )*'
            )*
        )}
    /x
    REGEX;

    /**
     * @internal
     */
    public static function extractAfxOutOfEelLineAndSeparateItIntoPaths(array $matches): string
    {
        $wholeEelExpressionWithStartTags = $matches[0];

        $additionalAfxContentPathValues = [];

        $replaceAfxFunctionsInEelWithThisPathAndRememberAfx = function ($matches) use (&$additionalAfxContentPathValues): string {
            $index = count($additionalAfxContentPathValues);
            $additionalAfxContentPathValues[] = $matches['afx'];
            return "Mhs.AfxContent.render(this, $index)";
        };

        $cleanedEelLine = preg_replace_callback(
            self::PATTERN_AFX_IN_EEL_EXPRESSION,
            $replaceAfxFunctionsInEelWithThisPathAndRememberAfx,
            $wholeEelExpressionWithStartTags
        );

        if (empty($additionalAfxContentPathValues)) {
            return $wholeEelExpressionWithStartTags;
        }

        $indent = $matches['indent'];
        $objectPath = $matches['objectPath'];

        $lineStart = $indent . self::guessFromObjectPathOfLineWhereToApplyTheAfxSnippetSoItsAvailableViaThis($objectPath);


        $newFusionLines = '';
        foreach ($additionalAfxContentPathValues as $index => $afxContentPathValue) {
            $newFusionLines .= "{$lineStart}@afxContent.$index = afx`$afxContentPathValue`\n";
        }
        $newFusionLines .= $cleanedEelLine;

        return $newFusionLines;
    }

    /**
     * @Flow\Around("method(Neos\Fusion\Core\Parser->parse())")
     * @param JoinPointInterface $joinPoint
     */
    public function extractAfxOutOfEel(JoinPointInterface $joinPoint)
    {
        $sourceCode = $joinPoint->getMethodArgument('sourceCode');

        $newSourceCode = preg_replace_callback(
            self::PATTERN_OBJECT_PATH_EEL_EXPRESSION_ASSIGN,
            [self::class, 'extractAfxOutOfEelLineAndSeparateItIntoPaths'],
            $sourceCode
        );

        if ($newSourceCode === null) {
            throw new \Exception("newSourceCode should be string. preg_replace_callback error.");
        }

        $joinPoint->setMethodArgument('sourceCode', $newSourceCode);

        return $joinPoint->getAdviceChain()->proceed($joinPoint);
    }

    protected const PATTERN_GET_PARENT_OBJECT_PATH = <<<'REGEX'
    /
        (?:
            prototype\(.*?\)
            |"(?:\\"|[^"])+"
            |'(?:\\'|[^'])+'
            |@[a-zA-Z0-9:_-]+
            |[a-zA-Z0-9:_-]+
        )\.
    /Ax
    REGEX;

    /**
     * hacky and unreliable way! see failing tests of this whole approach
     *
     * 'value' => ''
     * 'foo.bar' => 'foo.'
     * '@ process.bar' => ''
     *
     */
    protected static function guessFromObjectPathOfLineWhereToApplyTheAfxSnippetSoItsAvailableViaThis(string $objectPath): string
    {
        if (!preg_match_all(self::PATTERN_GET_PARENT_OBJECT_PATH, $objectPath, $matches)) {
            return '';
        }

        $upper = '';

        $paths = $matches[0];
        $pathsLastIndex = count($paths) - 1;

        foreach ($paths as $i => $path) {
            if ($i === $pathsLastIndex
                && strpos($path, '@') === 0) {
                break;
            }
            $upper .= $path;
        }
        return $upper;
    }
}