<?php

namespace Nexph\Compiler;

class PropsExtractor
{
    public function extractFromTag(string $tag): array
    {
        $props = [];
        
        if (preg_match_all('/(\w+)="([^"]*)"/', $tag, $matches)) {
            foreach ($matches[1] as $i => $name) {
                $props[$name] = $matches[2][$i];
            }
        }
        
        if (preg_match_all('/:(\w+)="([^"]*)"/', $tag, $matches)) {
            foreach ($matches[1] as $i => $name) {
                $props[$name] = [
                    'type' => 'binding',
                    'value' => $matches[2][$i],
                ];
            }
        }
        
        return $props;
    }
    
    public function convertType(string $value, string $type): mixed
    {
        return match($type) {
            'bool' => $value === 'true' || $value === '1',
            'int' => (int) $value,
            'float' => (float) $value,
            'array' => json_decode($value, true) ?? [],
            default => $value,
        };
    }
    
    public function validateProps(array $props, array $propTypes): array
    {
        $errors = [];
        
        foreach ($propTypes as $name => $rules) {
            $ruleList = explode('|', $rules);
            
            if (in_array('required', $ruleList) && !isset($props[$name])) {
                $errors[] = "Prop '{$name}' is required";
            }
        }
        
        return $errors;
    }
}
