<?php
namespace Core\Runtime\Package;

class PackagePublisher
{
    private PackageRegistryClient $registry;
    private PackageVerifier $verifier;

    public function __construct(PackageRegistryClient $registry)
    {
        $this->registry = $registry;
        $this->verifier = new PackageVerifier();
    }

    public function publish(string $packageDir): array
    {
        $verification = $this->verifier->verifyManifest($packageDir);
        if (!$verification['valid']) {
            return ['published' => false, 'error' => $verification['error']];
        }

        // TODO: implement actual publish to registry
        // For now, validate and return dry-run result
        return [
            'published' => false,
            'dry_run' => true,
            'name' => $verification['name'],
            'version' => $verification['version'],
            'message' => 'Registry publish not yet implemented',
        ];
    }

    public function pack(string $packageDir, string $outputDir): ?string
    {
        $verification = $this->verifier->verifyManifest($packageDir);
        if (!$verification['valid']) {
            return null;
        }

        $name = str_replace('/', '-', $verification['name']);
        $version = $verification['version'];
        $filename = "{$name}-{$version}.tar.gz";
        $outputPath = rtrim($outputDir, '/') . '/' . $filename;

        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $cmd = sprintf(
            'tar -czf %s -C %s .',
            escapeshellarg($outputPath),
            escapeshellarg($packageDir)
        );
        exec($cmd, $output, $exitCode);

        return $exitCode === 0 ? $outputPath : null;
    }
}
