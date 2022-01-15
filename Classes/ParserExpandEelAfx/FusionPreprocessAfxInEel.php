<?php

namespace MhsDesign\FusionAfxInEel\ParserExpandEelAfx;

use MhsDesign\FusionAfxInEel\AfxContent\Helper\AfxContentHelper;

class FusionPreprocessAfxInEel
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

    protected const PATTERN_REVERSED_ARROW_FUNCTION_PARAMETER = <<<'REGEX'
    /
      ^>=\s*+
      (?>
        (?P<single>[a-zA-Z0-9_-]++)
        |(?P<tuple>
          \)
            \s*+
            [a-zA-Z0-9_-]++
            \s*+
            (?:
              ,
              \s*+
              [a-zA-Z0-9_-]++
              \s*+
            )*+
          \(
        )
      )
      /x
    REGEX;

    protected const PATTERN_CHAINED_METHOD_NAME = <<<'REGEX'
    /
      ^\.\s*
      (?<chainedMethodName>[a-zA-Z_][a-zA-Z0-9_\-]*)
    /x
    REGEX;

    public static function extractAfxFromEelAndMakeItAccessibleWithHelperAndPath(string $fusionCode): ?string
    {
        $extractedAfxWithHash = [];
        $newSourceCode = preg_replace_callback(
            self::PATTERN_OBJECT_PATH_EEL_EXPRESSION_ASSIGN,
            function ($matches) use (&$extractedAfxWithHash) {
                return self::extractAfxOutOfEelLineAndSeparateItIntoPaths($matches, $extractedAfxWithHash);
            },
            $fusionCode
        );

        if (is_string($newSourceCode) === false) {
            throw new \Exception("newSourceCode should be string. preg_replace_callback error.");
        }

        $newSourceCodeWithAfxPath = $newSourceCode
            . self::renderAfxContentFusionLines($extractedAfxWithHash);

        return $newSourceCodeWithAfxPath;
    }

    protected static function renderAfxContentFusionLines(array $extractedAfxWithHash): string
    {
        $fusionLines = "";
        foreach ($extractedAfxWithHash as $hash => $afxContent) {
            if (strpos($afxContent, '`') !== false) {
                throw new \Exception("Eel Afx cannot contain backtick: '`'.", 1641935605);
            }
            $fusionLines .= "\n@afxContent.'$hash' = afx`$afxContent`";
        }
        return $fusionLines;
    }

    protected static function extractAfxOutOfEelLineAndSeparateItIntoPaths(array $matches, array &$extractedAfxWithHash): string
    {
        // can be eel multiline also.
        $fusionLineObjectPathWithEel = $matches[0];
        $eelExpressionContent = $matches['exp'];
        $indent = $matches['indent'];
        $objectPath = $matches['objectPath'];

        $eelContentWithOutAfx = preg_replace_callback(
            self::PATTERN_AFX_IN_EEL_EXPRESSION,
            function ($matches) use ($eelExpressionContent, &$extractedAfxWithHash) {
                return self::extractAfxFromAfxFunctionsAndReplaceWithAfxContentHelperAndAfxIndex($eelExpressionContent, $matches, $extractedAfxWithHash);
            },
            $eelExpressionContent,
            -1,
            $count,
            PREG_OFFSET_CAPTURE
        );

        if ($count === 0) {
            return $fusionLineObjectPathWithEel;
        }

        return "{$indent}{$objectPath} = " . '${' . $eelContentWithOutAfx .'}';
    }

    protected static function tryGetPrecedingParameterListOfArrowClosureSyntax(string $beforeAfxFunction): ?array
    {
        $reversed = strrev(rtrim($beforeAfxFunction));
        if (substr($reversed, 0, 2) !== '>=') {
            return null;
        }
        // >=  eulav
        // >=  )xedni, eulav(
        if (!preg_match(self::PATTERN_REVERSED_ARROW_FUNCTION_PARAMETER, $reversed, $matches, PREG_UNMATCHED_AS_NULL)) {
            return null;
        }
        if (isset($matches['single'])) {
            return [
                strrev($matches['single'])
            ];
        }
        $tupleValuesInside = substr(strrev($matches['tuple']), 1, -1);
        $paramsWithWhiteSpace = explode(',', $tupleValuesInside);
        $normalizedParams = array_map('trim', $paramsWithWhiteSpace);
        return $normalizedParams;

    }

    protected static function tryGetSucceedingChainedMethodName(string $partialEelExpression)
    {
        $partialEelExpression = ltrim($partialEelExpression);
        if (empty($partialEelExpression) || $partialEelExpression[0] !== '.') {
            return null;
        }
        if (!preg_match(self::PATTERN_CHAINED_METHOD_NAME, $partialEelExpression, $matches)) {
            return null;
        }
        return $matches['chainedMethodName'];
    }

    protected static function extractAfxFromAfxFunctionsAndReplaceWithAfxContentHelperAndAfxIndex(string $eelExpressionContent, array $matches, array &$extractedAfxWithHash): string
    {
        [$afx] = $matches['afx'];

        $hash = md5($afx);
        $extractedAfxWithHash[$hash] = $afx;

        [$completeAfxFunction, $afxFunctionStart] = $matches[0];

        $beforeAfxFunction = substr($eelExpressionContent, 0, $afxFunctionStart);
        $closureParams = self::tryGetPrecedingParameterListOfArrowClosureSyntax($beforeAfxFunction);

        $afxFunctionEnd = $afxFunctionStart + strlen($completeAfxFunction);
        $afterAfxFunction = substr($eelExpressionContent, $afxFunctionEnd);
        $chainedMethodName = self::tryGetSucceedingChainedMethodName($afterAfxFunction);

        $isChainedWithKnownMethod = isset($chainedMethodName)
            && in_array($chainedMethodName, AfxContentHelper::CHAINABLE_METHODS, true);

        return self::renderAfxContentEelHelper($hash, $isChainedWithKnownMethod, $closureParams);
    }

    protected static function renderAfxContentEelHelper(string $afxContentPathHash, string $isChained, ?array $contextParamList)
    {
        $isChainedArg = $isChained ? 'true' : 'false';

        $contextArg = 'null';
        if ($contextParamList !== null) {
            $contextKeyValueStringList = array_map(
                fn(string $param) => "$param: $param",
                $contextParamList
            );
            $contextArg = '{' . join(', ', $contextKeyValueStringList) . '}';
        }

        return "Mhs.AfxContent.new(this.runtime, '$afxContentPathHash', $isChainedArg, $contextArg)";
    }
}
