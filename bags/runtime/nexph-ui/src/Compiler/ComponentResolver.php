<?php

namespace Nexph\Compiler;

use Nexph\Runtime\ComponentRegistry;

class ComponentResolver
{
    private array $resolved = [];
    
    public function resolve(string $html): array
    {
        $components = [];
        
        if (preg_match_all('/<([A-Z][a-zA-Z0-9]*)([^>]*)>/', $html, $matches)) {
            foreach ($matches[1] as $i => $componentName) {
                if (!in_array($componentName, $components)) {
                    $components[] = $componentName;
                }
            }
        }
        
        if (preg_match_all('/<([a-z]+-[a-z-]+)([^>]*)>/', $html, $matches)) {
            foreach ($matches[1] as $i => $componentName) {
                if (!in_array($componentName, $components)) {
                    $components[] = $componentName;
                }
            }
        }
        
        return $components;
    }
    
    public function isComponent(string $tagName): bool
    {
        if (preg_match('/^[A-Z]/', $tagName)) {
            return true;
        }
        
        if (preg_match('/^[a-z]+-[a-z-]+$/', $tagName)) {
            return ComponentRegistry::has($tagName);
        }
        
        return false;
    }
    
    public function resolveNested(string $html, callable $compiler): string
    {
        return preg_replace_callback('/<([A-Z][a-zA-Z0-9]*)([^>]*)>(.*?)<\/\1>/s', function($matches) use ($compiler) {
            $componentName = $matches[1];
            $attributes = $matches[2];
            $content = $matches[3];
            
            $class = ComponentRegistry::resolve($componentName);
            
            if (!$class) {
                return $matches[0];
            }
            
            $propsExtractor = new PropsExtractor();
            $props = $propsExtractor->extractFromTag($attributes);
            
            $slotProcessor = new SlotProcessor();
            $slots = $slotProcessor->extractSlots($content);
            
            return call_user_func($compiler, $class, $props, $slots);
        }, $html);
    }
}
