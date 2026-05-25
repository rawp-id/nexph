<?php

namespace Nexph\Plugin;

/**
 * Phase 8: Plugin system foundation.
 * All plugins implement this interface to register directives,
 * compiler hooks, runtime injectors, and devtools tabs.
 */
interface NexphPlugin
{
    /**
     * Unique plugin identifier.
     */
    public function name(): string;

    /**
     * Called once when the plugin is registered.
     * Use to register directives, hooks, etc. via the registry.
     */
    public function register(PluginRegistry $registry): void;
}
