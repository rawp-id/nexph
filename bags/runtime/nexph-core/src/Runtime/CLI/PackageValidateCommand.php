<?php
namespace Core\Runtime\CLI;

class PackageValidateCommand extends Command
{
    protected string $name = 'package:validate';
    protected string $description = 'Validate a package manifest and structure';

    public function execute(array $args = []): int
    {
        require_once __DIR__ . '/../Loader/ManifestParser.php';
        require_once __DIR__ . '/../Loader/ManifestValidator.php';
        require_once __DIR__ . '/../Loader/Exceptions/ManifestValidationException.php';
        require_once __DIR__ . '/../Package/PackageVerifier.php';

        $parsed = $this->parseArgs($args);
        $dir = $parsed['arguments'][0] ?? '.';
        $json = isset($parsed['options']['json']);

        $targetDir = realpath($dir) ?: getcwd();
        $manifestPath = $targetDir . '/nexph.json';

        $errors = [];
        $warnings = [];

        // Validate manifest
        if (!file_exists($manifestPath)) {
            $errors[] = "nexph.json not found in {$targetDir}";
        } else {
            $parser = new \Core\Runtime\Loader\ManifestParser();
            $validator = new \Core\Runtime\Loader\ManifestValidator();

            try {
                $data = $parser->parse($manifestPath);
                $validator->validate($data, $manifestPath);
            } catch (\Core\Runtime\Loader\Exceptions\ManifestValidationException $e) {
                $errors = array_merge($errors, $e->getErrors());
            } catch (\Throwable $e) {
                $errors[] = $e->getMessage();
            }

            if (empty($errors)) {
                // Additional structure checks
                if (isset($data['autoload']['psr-4'])) {
                    foreach ($data['autoload']['psr-4'] as $ns => $path) {
                        $fullPath = $targetDir . '/' . $path;
                        if (!is_dir($fullPath)) {
                            $warnings[] = "Autoload path not found: {$path}";
                        }
                    }
                }

                if (isset($data['routes'])) {
                    foreach ((array)$data['routes'] as $route) {
                        if (!file_exists($targetDir . '/' . $route)) {
                            $warnings[] = "Route file not found: {$route}";
                        }
                    }
                }
            }
        }

        if ($json) {
            $this->output(json_encode([
                'valid' => empty($errors),
                'errors' => $errors,
                'warnings' => $warnings,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            return empty($errors) ? 0 : 1;
        }

        if (!empty($errors)) {
            $this->error("✗ Package validation failed:");
            foreach ($errors as $err) {
                $this->error("  ✗ {$err}");
            }
            return 1;
        }

        $this->output("✓ Package is valid: {$data['name']} ({$data['version']})");

        if (!empty($warnings)) {
            foreach ($warnings as $warn) {
                $this->output("  ⚠ {$warn}");
            }
        }

        return 0;
    }
}
