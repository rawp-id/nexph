<?php

namespace Nexph\Compiler;

class Compiler
{
    private Parser $parser;
    private HtmlGenerator $htmlGenerator;
    private CssExtractor $cssExtractor;
    private JsGenerator $jsGenerator;
    private StyleExtractor $styleExtractor;
    private StyleScoper $styleScoper;
    private ReactivityAnalyzer $reactivityAnalyzer;
    private CompilerCache $cache;

    public function __construct(?CompilerCache $cache = null)
    {
        $this->parser               = new Parser();
        $this->htmlGenerator        = new HtmlGenerator();
        $this->cssExtractor         = new CssExtractor();
        $this->jsGenerator          = new JsGenerator();
        $this->styleExtractor       = new StyleExtractor();
        $this->styleScoper          = new StyleScoper();
        $this->reactivityAnalyzer   = new ReactivityAnalyzer();
        $this->cache                 = $cache ?? new CompilerCache();
    }

    public function compile(string $source): array
    {
        // Phase 5: cache lookup
        $cacheKey = $this->cache->key($source);
        $cached   = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $ast       = $this->parser->parse($source);
        $scopeId   = $this->styleScoper->scopeId($ast['class'] ?? 'unknown');
        $rawCss    = $this->styleExtractor->extract($ast);
        $scopedCss = $rawCss ? $this->styleScoper->scope($rawCss, $scopeId) : $this->cssExtractor->extract($ast);

        // Phase 1+2: inject data-nx-id, build dep map and node ref map
        $rawHtml  = $this->htmlGenerator->generate($ast, $scopeId);
        $analyzed = $this->reactivityAnalyzer->analyze($rawHtml);

        $result = [
            'html'     => $analyzed['html'],
            'css'      => $scopedCss,
            'js'       => $this->jsGenerator->generate($ast, $analyzed['deps'], $analyzed['refs']),
            'manifest' => $this->generateManifest($ast),
        ];

        $this->cache->put($cacheKey, $result);
        return $result;
    }

    public function getCache(): CompilerCache
    {
        return $this->cache;
    }

    private function generateManifest(array $ast): array
    {
        return [
            'component' => $ast['class'] ?? 'Unknown',
            'events' => [],
            'state' => [],
        ];
    }
}
