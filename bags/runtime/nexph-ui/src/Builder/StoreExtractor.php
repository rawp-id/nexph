<?php

namespace Nexph\Builder;

/**
 * Extracts Store::define() calls from PHP source without executing the file.
 */
class StoreExtractor
{
    /**
     * Parse Store::define() calls from PHP source.
     * Returns [ 'storeName' => ['state' => [...], 'actions' => [...]] ]
     * Actions are extracted as JS function stubs (name only — bodies defined in JS).
     */
    public function extract(string $source): array
    {
        $stores = [];

        // Match: Store::define('name', [...], [...])
        if (!preg_match_all(
            '/Store::define\s*\(\s*[\'"](\w+)[\'"]\s*,\s*(\[.*?\])\s*(?:,\s*(\[.*?\]))?\s*\)/s',
            $source,
            $matches
        )) {
            return $stores;
        }

        foreach ($matches[1] as $i => $name) {
            $statePhp   = $matches[2][$i];
            $actionsPhp = $matches[3][$i] ?? '[]';

            $stores[$name] = [
                'state'   => $this->parsePhpArray($statePhp),
                'actions' => $this->parseActionNames($actionsPhp),
            ];
        }

        return $stores;
    }

    /**
     * Parse a simple PHP array literal into a PHP array.
     * Handles: string, int, float, bool, null, empty array values.
     */
    private function parsePhpArray(string $php): array
    {
        $result = [];
        $php    = trim($php);

        // strip outer [ ]
        if (str_starts_with($php, '[') && str_ends_with($php, ']')) {
            $php = substr($php, 1, -1);
        }

        // split on commas not inside nested brackets
        $entries = $this->splitEntries($php);

        foreach ($entries as $entry) {
            $entry = trim($entry);
            if (empty($entry)) continue;

            if (preg_match('/^[\'"](\w+)[\'"]\s*=>\s*(.+)$/s', $entry, $m)) {
                $key   = $m[1];
                $value = trim($m[2]);
                $result[$key] = $this->parseValue($value);
            }
        }

        return $result;
    }

    private function parseValue(string $value): mixed
    {
        if ($value === '[]') return [];
        if ($value === 'true') return true;
        if ($value === 'false') return false;
        if ($value === 'null') return null;
        if (is_numeric($value)) {
            return str_contains($value, '.') ? (float)$value : (int)$value;
        }
        if (preg_match('/^[\'"](.*)[\'"]\s*$/s', $value, $m)) {
            return $m[1];
        }
        return $value;
    }

    /**
     * Extract action names from actions array literal.
     * Returns [ 'actionName' => null ] — bodies defined in JS.
     */
    private function parseActionNames(string $php): array
    {
        $actions = [];
        $php     = trim($php);

        if ($php === '[]') return $actions;

        if (str_starts_with($php, '[') && str_ends_with($php, ']')) {
            $php = substr($php, 1, -1);
        }

        if (preg_match_all('/[\'"](\w+)[\'"]\s*=>/', $php, $m)) {
            foreach ($m[1] as $name) {
                $actions[$name] = null;
            }
        }

        return $actions;
    }

    private function splitEntries(string $php): array
    {
        $entries = [];
        $depth   = 0;
        $current = '';
        $len     = strlen($php);

        for ($i = 0; $i < $len; $i++) {
            $ch = $php[$i];
            if ($ch === '[') $depth++;
            elseif ($ch === ']') $depth--;
            elseif ($ch === ',' && $depth === 0) {
                $entries[] = $current;
                $current   = '';
                continue;
            }
            $current .= $ch;
        }

        if (trim($current) !== '') {
            $entries[] = $current;
        }

        return $entries;
    }
}
