<?php
namespace Tests\Unit;

use Core\Database\Metadata;

class MetadataTest {
    private string $testMetaPath;

    public function setUp(): void {
        $this->testMetaPath = __DIR__ . '/../../metadata';
    }

    public function testLoadExistingMetadata(): void {
        $meta = Metadata::load('users');
        $this->assert(is_array($meta), 'Load returns array');
        $this->assert(isset($meta['table']), 'Metadata has table key');
    }

    public function testLoadNonExistentMetadata(): void {
        $exception = false;
        try {
            Metadata::load('nonexistent_table_xyz');
        } catch (\Exception $e) {
            $exception = true;
            $this->assert(strpos($e->getMessage(), 'not found') !== false, 'Exception message correct');
        }
        $this->assert($exception, 'Throws exception for missing metadata');
    }

    public function testLoadCaching(): void {
        $meta1 = Metadata::load('users');
        $meta2 = Metadata::load('users');
        $this->assert($meta1 === $meta2, 'Load caches results');
    }

    public function testAllMetadata(): void {
        $all = Metadata::all();
        $this->assert(is_array($all), 'All returns array');
        $this->assert(count($all) > 0, 'All returns metadata files');
    }

    public function testAllCaching(): void {
        $all1 = Metadata::all();
        $all2 = Metadata::all();
        $this->assert($all1 === $all2, 'All caches results');
    }

    public function testMetadataStructure(): void {
        $meta = Metadata::load('users');
        $this->assert(isset($meta['table']), 'Has table');
        $this->assert(isset($meta['fields']), 'Has fields');
        $this->assert(is_array($meta['fields']), 'Fields is array');
    }

    public function testPathTraversalPrevention(): void {
        $exception = false;
        try {
            Metadata::load('../../../etc/passwd');
        } catch (\Exception $e) {
            $exception = true;
        }
        $this->assert($exception, 'Prevents path traversal');
    }

    private function assert(bool $condition, string $message): void {
        if ($condition) {
            echo "✓ {$message}\n";
        } else {
            echo "✗ {$message}\n";
            exit(1);
        }
    }

    public static function run(): void {
        $test = new self();
        
        $methods = get_class_methods($test);
        foreach ($methods as $method) {
            if (strpos($method, 'test') === 0) {
                $test->setUp();
                $test->$method();
            }
        }
        
        echo "\nAll Metadata tests passed\n";
    }
}

if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    require_once __DIR__ . '/../../autoload.php';
    MetadataTest::run();
}
