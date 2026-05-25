<?php
namespace Core\Runtime\Package;

class PackageUpdater
{
    private PackageManager $manager;

    public function __construct(PackageManager $manager)
    {
        $this->manager = $manager;
    }

    public function updateAll(): array
    {
        return $this->manager->update();
    }

    public function updatePackage(string $package): array
    {
        return $this->manager->update($package);
    }

    public function checkOutdated(): array
    {
        $lock = $this->manager->getLock();
        $manifest = $this->manager->getManifest();
        $outdated = [];

        foreach ($manifest->getDependencies() as $name => $constraint) {
            $locked = $lock->getPackage($name);
            if ($locked === null) {
                $outdated[] = ['name' => $name, 'status' => 'not installed'];
            }
        }

        return $outdated;
    }
}
