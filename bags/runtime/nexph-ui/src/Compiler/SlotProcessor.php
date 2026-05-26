<?php

namespace Nexph\Compiler;

class SlotProcessor
{
    public function extractSlots(string $html): array
    {
        $slots = ['default' => ''];

        if (preg_match_all('/<template\s+slot="(\w+)">(.*?)<\/template>/s', $html, $matches)) {
            foreach ($matches[1] as $i => $name) {
                $slots[$name] = trim($matches[2][$i]);
            }
        }

        $defaultContent = preg_replace('/<template\s+slot="[^"]+">.*?<\/template>/s', '', $html);
        $slots['default'] = trim($defaultContent);

        return $slots;
    }

    public function processSlotPlaceholders(string $html, array $slots): string
    {
        $html = preg_replace_callback('/<slot\s+name="(\w+)"\s*\/>/', function($matches) use ($slots) {
            $name = $matches[1];
            return $slots[$name] ?? '';
        }, $html);

        $html = preg_replace_callback('/<slot\s*\/>/', function($matches) use ($slots) {
            return $slots['default'] ?? '';
        }, $html);

        return $html;
    }

    public function extractScopedSlotData(string $html): array
    {
        $scopedData = [];

        if (preg_match_all('/<slot([^>]+)\/>/s', $html, $slotMatches)) {
            foreach ($slotMatches[1] as $attrs) {
                if (preg_match_all('/:(\w+)="([^"]+)"/', $attrs, $bindMatches)) {
                    foreach ($bindMatches[1] as $i => $key) {
                        $scopedData[$key] = $bindMatches[2][$i];
                    }
                }
            }
        }

        return $scopedData;
    }
}
