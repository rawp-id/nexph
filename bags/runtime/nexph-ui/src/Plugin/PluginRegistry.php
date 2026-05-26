<?php

namespace Nexph\Plugin;

/**
 * Phase 8: Plugin registry.
 * Plugins register directives, compiler hooks, runtime injectors, devtools tabs.
 */
class PluginRegistry
{
    /** @var NexphPlugin[] */
    private array $plugins = [];

    /** @var array<string, callable> directive name → HTML transformer */
    private array $directives = [];

    /** @var callable[] compiler hooks: fn(array $ast): array */
    private array $compilerHooks = [];

    /** @var callable[] runtime injectors: fn(string $html): string — return JS chunk */
    private array $runtimeInjectors = [];

    /** @var array<string, callable> devtools tab name → fn(): string (JS snippet) */
    private array $devtoolsTabs = [];

    public function register(NexphPlugin $plugin): void
    {
        $name = $plugin->name();
        if (isset($this->plugins[$name])) return;
        $this->plugins[$name] = $plugin;
        $plugin->register($this);
    }

    // ── Directive registration ────────────────────────────────────────────────

    /**
     * Register a custom directive transformer.
     * $transformer receives (string $attrValue, string $html): string
     * and should return the modified HTML.
     */
    public function addDirective(string $directiveName, callable $transformer): void
    {
        $this->directives[$directiveName] = $transformer;
    }

    public function getDirectives(): array
    {
        return $this->directives;
    }

    // ── Compiler hooks ────────────────────────────────────────────────────────

    /**
     * Register a compiler hook that transforms the AST before JS generation.
     * $hook receives (array $ast): array
     */
    public function addCompilerHook(callable $hook): void
    {
        $this->compilerHooks[] = $hook;
    }

    /**
     * Run all compiler hooks on an AST, returning the final AST.
     */
    public function runCompilerHooks(array $ast): array
    {
        foreach ($this->compilerHooks as $hook) {
            $ast = $hook($ast);
        }
        return $ast;
    }

    // ── Runtime injectors ─────────────────────────────────────────────────────

    /**
     * Register a runtime injector.
     * $injector receives (string $compiledHtml): ?string
     * Return a JS chunk string to inject, or null to skip.
     */
    public function addRuntimeInjector(callable $injector): void
    {
        $this->runtimeInjectors[] = $injector;
    }

    /**
     * Run all runtime injectors against compiled HTML.
     * Returns array of JS chunks to append to the bundle.
     */
    public function runRuntimeInjectors(string $html): array
    {
        $chunks = [];
        foreach ($this->runtimeInjectors as $injector) {
            $result = $injector($html);
            if ($result !== null && $result !== '') {
                $chunks[] = $result;
            }
        }
        return $chunks;
    }

    // ── DevTools tabs ─────────────────────────────────────────────────────────

    /**
     * Register a custom DevTools tab.
     * $renderer is a JS string (IIFE) that registers the tab via window.NEXPH.devtools.addTab(name, fn).
     */
    public function addDevtoolsTab(string $tabName, string $jsRenderer): void
    {
        $this->devtoolsTabs[$tabName] = $jsRenderer;
    }

    public function getDevtoolsTabs(): array
    {
        return $this->devtoolsTabs;
    }

    // ── Introspection ─────────────────────────────────────────────────────────

    public function getPlugins(): array
    {
        return $this->plugins;
    }

    public function has(string $name): bool
    {
        return isset($this->plugins[$name]);
    }
}
