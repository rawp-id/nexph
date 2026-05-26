<?php
namespace Core\Runtime\Package;

class PackageVerifier
{
    public function verify(string $path, ?string $expectedChecksum = null): array
    {
        if (!file_exists($path)) {
            return ['valid' => false, 'error' => 'file not found'];
        }

        $actualChecksum = hash_file('sha256', $path);

        if ($expectedChecksum !== null && $actualChecksum !== $expectedChecksum) {
            return [
                'valid' => false,
                'error' => 'checksum mismatch',
                'expected' => $expectedChecksum,
                'actual' => $actualChecksum,
            ];
        }

        return ['valid' => true, 'checksum' => $actualChecksum];
    }

    public function checksumFile(string $path): ?string
    {
        if (!file_exists($path)) {
            return null;
        }
        return hash_file('sha256', $path);
    }

    public function verifyManifest(string $packageDir): array
    {
        $manifestPath = rtrim($packageDir, '/') . '/nexph.json';

        if (!file_exists($manifestPath)) {
            return ['valid' => false, 'error' => 'missing nexph.json'];
        }

        $content = file_get_contents($manifestPath);
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['valid' => false, 'error' => 'invalid JSON in nexph.json'];
        }

        if (empty($data['name']) || empty($data['version'])) {
            return ['valid' => false, 'error' => 'missing name or version'];
        }

        return ['valid' => true, 'name' => $data['name'], 'version' => $data['version']];
    }
}
