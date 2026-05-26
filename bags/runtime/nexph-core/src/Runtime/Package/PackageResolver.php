<?php
namespace Core\Runtime\Package;

class PackageResolver
{
    public function resolve(string $package, ?string $constraint = null): array
    {
        // TODO: implement registry lookup + semver resolution
        // For now, return stub for local/path packages
        return [
            'name' => $package,
            'version' => $constraint ?? '0.0.0',
            'source' => 'local',
            'checksum' => null,
            'requires' => [],
            'installed_path' => null,
        ];
    }
}
