<?php
namespace Core\Runtime\Package;

class SemverResolver
{
    public function satisfies(string $version, string $constraint): bool
    {
        $constraint = trim($constraint);

        if ($constraint === '*' || $constraint === '') {
            return true;
        }

        // Exact match
        if ($constraint === $version) {
            return true;
        }

        // || (OR) — check first
        if (str_contains($constraint, '||')) {
            $alternatives = explode('||', $constraint);
            foreach ($alternatives as $alt) {
                if ($this->satisfies($version, trim($alt))) {
                    return true;
                }
            }
            return false;
        }

        // Range: x.y.z - a.b.c
        if (str_contains($constraint, ' - ')) {
            [$lower, $upper] = explode(' - ', $constraint, 2);
            return version_compare($version, trim($lower), '>=') && version_compare($version, trim($upper), '<=');
        }

        // Multi-constraint: >=1.0.0 <2.0.0 (space-separated AND)
        if (str_contains($constraint, ' ')) {
            $parts = preg_split('/\s+/', $constraint);
            foreach ($parts as $part) {
                if (!$this->satisfies($version, $part)) {
                    return false;
                }
            }
            return true;
        }

        // >=x.y.z
        if (str_starts_with($constraint, '>=')) {
            return version_compare($version, substr($constraint, 2), '>=');
        }

        // <=x.y.z
        if (str_starts_with($constraint, '<=')) {
            return version_compare($version, substr($constraint, 2), '<=');
        }

        // >x.y.z
        if (str_starts_with($constraint, '>') && !str_starts_with($constraint, '>=')) {
            return version_compare($version, substr($constraint, 1), '>');
        }

        // <x.y.z
        if (str_starts_with($constraint, '<') && !str_starts_with($constraint, '<=')) {
            return version_compare($version, substr($constraint, 1), '<');
        }

        // !=x.y.z
        if (str_starts_with($constraint, '!=')) {
            return $version !== substr($constraint, 2);
        }

        // ^x.y.z (caret: >=x.y.z <next major)
        if (str_starts_with($constraint, '^')) {
            $base = substr($constraint, 1);
            $parts = explode('.', $base);
            $major = (int)($parts[0] ?? 0);

            if ($major === 0) {
                // ^0.y.z means >=0.y.z <0.(y+1).0
                $minor = (int)($parts[1] ?? 0);
                $upper = "0." . ($minor + 1) . ".0";
            } else {
                $upper = ($major + 1) . ".0.0";
            }

            return version_compare($version, $base, '>=') && version_compare($version, $upper, '<');
        }

        // ~x.y.z (tilde: >=x.y.z <x.(y+1).0)
        if (str_starts_with($constraint, '~')) {
            $base = substr($constraint, 1);
            $parts = explode('.', $base);
            $major = (int)($parts[0] ?? 0);
            $minor = (int)($parts[1] ?? 0);
            $upper = $major . "." . ($minor + 1) . ".0";

            return version_compare($version, $base, '>=') && version_compare($version, $upper, '<');
        }

        // x.y.* wildcard
        if (str_contains($constraint, '*')) {
            $prefix = rtrim(str_replace('*', '', $constraint), '.');
            return str_starts_with($version, $prefix);
        }

        // Plain version comparison
        return version_compare($version, $constraint, '==');
    }

    public function findBest(array $versions, string $constraint, bool $allowPreRelease = false): ?string
    {
        $matching = [];

        foreach ($versions as $v) {
            if (!$allowPreRelease && str_contains($v, '-')) {
                continue;
            }
            if ($this->satisfies($v, $constraint)) {
                $matching[] = $v;
            }
        }

        if (empty($matching)) {
            return null;
        }

        usort($matching, 'version_compare');
        return end($matching);
    }

    public function isStable(string $version): bool
    {
        return !str_contains($version, '-');
    }

    public function parse(string $version): array
    {
        $parts = explode('-', $version, 2);
        $numbers = explode('.', $parts[0]);

        return [
            'major' => (int)($numbers[0] ?? 0),
            'minor' => (int)($numbers[1] ?? 0),
            'patch' => (int)($numbers[2] ?? 0),
            'prerelease' => $parts[1] ?? null,
        ];
    }
}
