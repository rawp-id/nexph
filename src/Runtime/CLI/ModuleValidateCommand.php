<?php
namespace Core\Runtime\CLI;

class ModuleValidateCommand extends Command
{
    protected string $name = 'module:validate';
    protected string $description = 'Validate a package manifest';

    public function execute(array $args = []): int
    {
        require_once __DIR__ . '/../Loader/ManifestParser.php';
        require_once __DIR__ . '/../Loader/ManifestValidator.php';
        require_once __DIR__ . '/../Loader/Exceptions/ManifestValidationException.php';

        $parsed = $this->parseArgs($args);
        $path = $parsed['arguments'][0] ?? 'nexph.json';

        $parser = new \Core\Runtime\Loader\ManifestParser();
        $validator = new \Core\Runtime\Loader\ManifestValidator();

        try {
            $data = $parser->parse($path);
            $validator->validate($data, $path);
            $this->output("✓ Valid manifest: {$path}");
            $this->output("  name: {$data['name']}");
            $this->output("  version: {$data['version']}");
            $this->output("  type: " . ($data['type'] ?? 'library'));
            return 0;
        } catch (\Core\Runtime\Loader\Exceptions\ManifestValidationException $e) {
            $this->error("✗ Invalid manifest: {$path}");
            foreach ($e->getErrors() as $err) {
                $this->error("  - {$err}");
            }
            return 1;
        } catch (\Throwable $e) {
            $this->error("✗ Error: {$e->getMessage()}");
            return 1;
        }
    }
}
