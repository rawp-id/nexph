<?php

namespace Nexph\Builder;

use Nexph\Compiler\Compiler;
use Nexph\Compiler\CompilerCache;
use Nexph\Plugin\PluginRegistry;
use Nexph\Runtime\RuntimeCore;
use Nexph\Runtime\RuntimeRouter;
use Nexph\Runtime\RuntimeStore;
use Nexph\Runtime\RuntimeValidate;
use Nexph\Runtime\RuntimeLazy;
use Nexph\Runtime\RuntimeAnimate;
use Nexph\Runtime\RuntimePortal;
use Nexph\Runtime\RuntimeDevtools;
use Nexph\Builder\SpaFallbackGenerator;

class BuildPipeline
{
    private Compiler         $compiler;
    private DependencyResolver $dependencyResolver;
    private AssetHasher      $hasher;
    private HtmlMinifier     $htmlMinifier;
    private CssMinifier      $cssMinifier;
    private JsMinifier       $jsMinifier;
    private ManifestGenerator $manifestGenerator;
    private SourceMapGenerator $sourceMapGenerator;
    private GzipCompressor   $gzip;
    private PwaGenerator     $pwa;
    private BundleAnalyzer   $analyzer;
    private StoreExtractor   $storeExtractor;
    private PluginRegistry   $plugins;

    // Phase 4: modular runtime modules
    private RuntimeCore      $runtimeCore;
    private RuntimeRouter    $runtimeRouter;
    private RuntimeStore     $runtimeStore;
    private RuntimeValidate  $runtimeValidate;
    private RuntimeLazy      $runtimeLazy;
    private RuntimeAnimate   $runtimeAnimate;
    private RuntimePortal    $runtimePortal;
    private RuntimeDevtools  $runtimeDevtools;
    private SpaFallbackGenerator $spaFallback;

    public function __construct(?CompilerCache $cache = null, ?PluginRegistry $plugins = null)
    {
        $this->compiler           = new Compiler($cache);
        $this->dependencyResolver = new DependencyResolver();
        $this->hasher             = new AssetHasher();
        $this->htmlMinifier       = new HtmlMinifier();
        $this->cssMinifier        = new CssMinifier();
        $this->jsMinifier         = new JsMinifier();
        $this->manifestGenerator  = new ManifestGenerator();
        $this->sourceMapGenerator = new SourceMapGenerator();
        $this->gzip               = new GzipCompressor();
        $this->pwa                = new PwaGenerator();
        $this->analyzer           = new BundleAnalyzer();
        $this->storeExtractor     = new StoreExtractor();
        $this->plugins            = $plugins ?? new PluginRegistry();

        // Phase 4: modular runtime
        $this->runtimeCore      = new RuntimeCore();
        $this->runtimeRouter    = new RuntimeRouter();
        $this->runtimeStore     = new RuntimeStore();
        $this->runtimeValidate  = new RuntimeValidate();
        $this->runtimeLazy      = new RuntimeLazy();
        $this->runtimeAnimate   = new RuntimeAnimate();
        $this->runtimePortal    = new RuntimePortal();
        $this->runtimeDevtools  = new RuntimeDevtools();
        $this->spaFallback      = new SpaFallbackGenerator();
    }

    public function run(string $entryFile, array $config): array
    {
        $outputDir = rtrim($config['output'], '/');
        $minify    = $config['minify']    ?? false;
        $compress  = $config['compress']  ?? false;
        $pwa       = $config['pwa']       ?? false;
        $routeMode   = $config['routeMode']   ?? 'hash';
        $spaFallback = $config['spaFallback'] ?? false;
        $analyze     = $config['analyze']     ?? false;

        // 1. Resolve dependency order
        $files = $this->dependencyResolver->resolve($entryFile);
        if (empty($files)) {
            $files = [realpath($entryFile)];
        }

        // 2. Compile each component
        $htmlParts  = [];
        $cssParts   = [];
        $jsParts    = [];
        $components = [];

        foreach ($files as $file) {
            $source = file_get_contents($file);
            $output = $this->compiler->compile($source);

            $htmlParts[]  = $output['html'];
            $cssParts[]   = $output['css'];
            $jsParts[]    = $output['js'];
            $components[] = $output['manifest']['component'] ?? basename($file, '.php');
        }

        // 3. Bundle component output
        $html = implode("\n", $htmlParts);
        $css  = implode("\n", $cssParts);
        $js   = implode("\n", $jsParts);

        // 4. Phase 4: inject runtime-core always, then conditional modules
        $runtimeChunks = [$this->runtimeCore->generate()];

        // router
        $routes = $this->runtimeRouter->extractRoutes($html);
        if (!empty($routes)) {
            $runtimeChunks[] = $this->runtimeRouter->generate($routes, $routeMode);
        }

        // store
        $storeNames      = $this->runtimeStore->extractStoreNames($html);
        $extractedStores = [];
        foreach ($files as $file) {
            $extracted       = $this->storeExtractor->extract(file_get_contents($file));
            $extractedStores = array_merge($extractedStores, $extracted);
        }
        $allStores = array_merge($extractedStores, $config['stores'] ?? []);
        if (!empty($storeNames) || !empty($allStores)) {
            $runtimeChunks[] = $this->runtimeStore->generate($allStores);
        }

        // validate
        if (str_contains($html, 'data-nexph-validate')) {
            $runtimeChunks[] = $this->runtimeValidate->generate();
        }

        // lazy
        if (str_contains($html, 'data-nexph-lazy')) {
            $runtimeChunks[] = $this->runtimeLazy->generate();
        }

        // animate
        if ($this->runtimeAnimate->hasAnimations($html)) {
            $runtimeChunks[] = $this->runtimeAnimate->generate();
        }

        // portal
        if ($this->runtimePortal->hasPortals($html)) {
            $runtimeChunks[] = $this->runtimePortal->generate();
        }

        // Phase 8: plugin runtime injectors
        foreach ($this->plugins->runRuntimeInjectors($html) as $chunk) {
            $runtimeChunks[] = $chunk;
        }

        $js = implode("\n", $runtimeChunks) . "\n" . $js;

        // 5. Optionally minify
        if ($minify) {
            $html = $this->htmlMinifier->minify($html);
            $css  = $this->cssMinifier->minify($css);
            $js   = $this->jsMinifier->minify($js);
        }

        // 6. Hash filenames
        $jsFilename  = 'assets/' . $this->hasher->hashFilename('app.js',  $js);
        $cssFilename = 'assets/' . $this->hasher->hashFilename('app.css', $css);

        // 7. Write output dirs
        $this->ensureDir($outputDir);
        $this->ensureDir($outputDir . '/assets');

        // 8. Source maps
        $sourcemap = $config['sourcemap'] ?? false;
        $mapFile   = null;
        if ($sourcemap) {
            $mapFile    = "{$outputDir}/assets/app.js.map";
            $js         = $this->sourceMapGenerator->generateFile($js, $entryFile, $mapFile, 'app.js');
            $jsFilename = 'assets/' . $this->hasher->hashFilename('app.js', $js);
        }

        // 9. Build index.html
        $hmrPort      = $config['hmrPort'] ?? 0;
        $devToolsInfo = [
            'entry'      => $entryFile,
            'components' => $components,
            'sizes'      => [
                'html'  => strlen($html),
                'js'    => strlen($js),
                'css'   => strlen($css),
                'total' => strlen($html) + strlen($js) + strlen($css),
            ],
        ];

        $indexHtml = $this->buildIndexHtml(
            $html, $jsFilename, $cssFilename,
            $config['tailwind']              ?? false,
            $hmrPort,
            $pwa,
            $config['pwaOptions']['theme_color'] ?? '#6366f1',
            $devToolsInfo
        );

        file_put_contents("{$outputDir}/index.html",    $indexHtml);
        file_put_contents("{$outputDir}/{$jsFilename}", $js);
        file_put_contents("{$outputDir}/{$cssFilename}", $css);

        // gzip sidecar files
        if ($compress) {
            $this->gzip->writeGz("{$outputDir}/index.html",    $indexHtml);
            $this->gzip->writeGz("{$outputDir}/{$jsFilename}", $js);
            $this->gzip->writeGz("{$outputDir}/{$cssFilename}", $css);
        }

        // 10. Bundle analysis
        $analysisReport = null;
        if ($analyze) {
            $analysisReport = $this->analyzer->analyze($components, $jsParts, $cssParts, $htmlParts);
            $this->analyzer->writeJson($analysisReport, $outputDir);
            echo $this->analyzer->render($analysisReport);
        }

        // 11. PWA
        $pwaFiles = [];
        if ($pwa) {
            $pwaFiles = $this->pwa->write(
                $outputDir,
                [$jsFilename, $cssFilename],
                $config['pwaOptions'] ?? []
            );
        }

        // 12. SPA fallback for history mode
        $fallbackFiles = [];
        if ($routeMode === 'history') {
            if ($spaFallback) {
                $fallbackFiles = $this->spaFallback->generate($outputDir, $config);
            } else {
                $this->spaFallback->printWarning();
            }
        }

        // 13. Manifest
        $assets = ['app.js' => $jsFilename, 'app.css' => $cssFilename];
        if ($sourcemap && $mapFile) $assets['app.js.map'] = basename($mapFile);
        if ($pwa) { $assets['manifest.webmanifest'] = 'manifest.webmanifest'; $assets['sw.js'] = 'sw.js'; }

        $manifest = $this->manifestGenerator->generate([
            'entry'      => $entryFile,
            'assets'     => $assets,
            'components' => $components,
            'size'       => [
                'html'  => strlen($indexHtml),
                'js'    => strlen($js),
                'css'   => strlen($css),
                'total' => strlen($indexHtml) + strlen($js) + strlen($css),
            ],
        ]);
        $this->manifestGenerator->write($manifest, $outputDir);

        $outputFiles = [
            'html'     => "{$outputDir}/index.html",
            'js'       => "{$outputDir}/{$jsFilename}",
            'css'      => "{$outputDir}/{$cssFilename}",
            'manifest' => "{$outputDir}/manifest.json",
        ];
        if ($sourcemap && $mapFile) $outputFiles['map'] = $mapFile;
        if ($pwa) { $outputFiles['pwa_manifest'] = $pwaFiles['manifest']; $outputFiles['sw'] = $pwaFiles['sw']; }
        if (!empty($fallbackFiles)) { $outputFiles['_redirects'] = $fallbackFiles['_redirects']; }

        return [
            'success'   => true,
            'outputDir' => $outputDir,
            'files'     => $outputFiles,
            'manifest'  => $manifest,
            'analysis'  => $analysisReport,
        ];
    }

    public function buildIndexHtml(
        string $body,
        string $jsFile,
        string $cssFile,
        bool   $tailwind    = false,
        int    $hmrPort     = 0,
        bool   $pwa         = false,
        string $themeColor  = '#6366f1',
        array  $devToolsInfo = []
    ): string {
        $tailwindTag  = $tailwind ? "\n    <script src=\"https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4\"></script>" : '';
        $pwaSnippet   = $pwa ? "\n    " . (new PwaGenerator())->headSnippet($themeColor) : '';
        $hmrScript    = $hmrPort > 0 ? $this->hmrClientScript($hmrPort) : '';

        // Phase 7: DevTools — plugin-aware, dev only
        $extraTabs      = $this->plugins->getDevtoolsTabs();
        $devToolsScript = $hmrPort > 0
            ? $this->runtimeDevtools->generate($devToolsInfo, $extraTabs)
            : '';

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NEXPH App</title>{$tailwindTag}{$pwaSnippet}
    <link rel="stylesheet" href="{$cssFile}">
</head>
<body>
{$body}
<script src="{$jsFile}"></script>{$hmrScript}
{$devToolsScript}
</body>
</html>
HTML;
    }

    private function hmrClientScript(int $port): string
    {
        return implode("\n", [
            '',
            '<script>',
            '(function(){',
            '    var ws,retry=0;',
            '    function connect(){',
            '        ws=new WebSocket("ws://localhost:' . $port . '");',
            '        ws.onmessage=function(e){',
            '            var msg=JSON.parse(e.data);',
            '            if(msg.type==="reload"){window.location.reload();}',
            '            if(msg.type==="error"){showError(msg.payload.message);}',
            '        };',
            '        ws.onclose=function(){ retry=Math.min(retry+1,10); setTimeout(connect,500*retry); };',
            '        ws.onerror=function(){ws.close();};',
            '    }',
            '    function showError(msg){',
            '        var el=document.getElementById("__nexph_err");',
            '        if(!el){',
            '            el=document.createElement("div");',
            '            el.id="__nexph_err";',
            '            el.style.cssText="position:fixed;top:0;left:0;right:0;z-index:99999;background:#1a1a1a;color:#ff5555;font:14px monospace;padding:16px 20px;white-space:pre-wrap;border-bottom:2px solid #ff5555;";',
            '            var close=document.createElement("button");',
            '            close.textContent="x";',
            '            close.style.cssText="float:right;background:none;border:none;color:#ff5555;font-size:18px;cursor:pointer;";',
            '            close.onclick=function(){el.remove();};',
            '            el.appendChild(close);',
            '            document.body.prepend(el);',
            '        }',
            '        el.childNodes[0].textContent="[NEXPH] Build error:\\n"+msg;',
            '    }',
            '    connect();',
            '})();',
            '</script>',
        ]);
    }

    private function ensureDir(string $dir): void
    {
        if (!is_dir($dir)) mkdir($dir, 0755, true);
    }
}
