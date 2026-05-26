<?php
namespace Core\Runtime\Package;

class PackageManager
{
    private PackageManifest $manifest;
    private PackageLock $lock;
    private PackageResolver $resolver;
    private PackageInstaller $installer;
    private PackageRemover $remover;
    private string $projectRoot;

    public function __construct(string $projectRoot)
    {
        $this->projectRoot = $projectRoot;
        $this->manifest = new PackageManifest($projectRoot . '/nexph.json');
        $this->lock = new PackageLock($projectRoot . '/nexph.lock');
        $this->resolver = new PackageResolver();
        $this->installer = new PackageInstaller($projectRoot);
        $this->remover = new PackageRemover($projectRoot);
    }

    public function install(string $package, ?string $version = null): array
    {
        $isComposer = str_starts_with($package, 'composer:');
        $packageName = $isComposer ? substr($package, 9) : $package;

        if ($isComposer) {
            return $this->installer->installComposer($packageName, $version);
        }

        $resolved = $this->resolver->resolve($packageName, $version);
        $result = $this->installer->installNative($resolved);

        $this->manifest->addDependency($packageName, $resolved['version']);
        $this->lock->addPackage($resolved);

        return $result;
    }

    public function remove(string $package): array
    {
        $result = $this->remover->remove($package);
        $this->manifest->removeDependency($package);
        $this->lock->removePackage($package);
        return $result;
    }

    public function update(?string $package = null): array
    {
        if ($package !== null) {
            $current = $this->lock->getPackage($package);
            $constraint = $this->manifest->getDependency($package);
            $resolved = $this->resolver->resolve($package, $constraint);

            if ($resolved['version'] !== ($current['version'] ?? null)) {
                $this->installer->installNative($resolved);
                $this->lock->addPackage($resolved);
                return ['updated' => [$package => $resolved['version']]];
            }

            return ['updated' => []];
        }

        $updated = [];
        foreach ($this->manifest->getDependencies() as $name => $constraint) {
            $result = $this->update($name);
            $updated = array_merge($updated, $result['updated']);
        }

        return ['updated' => $updated];
    }

    public function restore(): array
    {
        $packages = $this->lock->all();
        $installed = [];

        foreach ($packages as $pkg) {
            $this->installer->installNative($pkg);
            $installed[] = $pkg['name'];
        }

        return ['restored' => $installed];
    }

    public function list(): array
    {
        return $this->lock->all();
    }

    public function getManifest(): PackageManifest
    {
        return $this->manifest;
    }

    public function getLock(): PackageLock
    {
        return $this->lock;
    }
}
