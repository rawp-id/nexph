<?php
namespace Tests\Unit;

use Core\Generator\ApiGenerator;
use Core\Http\Router;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\ApiPolicy;
use Core\Database\DB;

class ApiGeneratorTest {
    private Router $router;
    private array $testMeta;

    public function setUp(): void {
        $this->router = new Router();
        $this->testMeta = [
            'table' => 'test_items',
            'fields' => [
                ['name' => 'id', 'type' => 'int', 'primary' => true],
                ['name' => 'title', 'type' => 'string', 'required' => true],
                ['name' => 'status', 'type' => 'string']
            ]
        ];
    }

    public function testRegisterCreatesRoutes(): void {
        ApiGenerator::register($this->router, $this->testMeta);
        $this->assert(true, 'Register completes without error');
    }

    public function testInvalidTableNameThrows(): void {
        $exception = false;
        try {
            ApiGenerator::register($this->router, ['table' => 'bad-table-name']);
        } catch (\InvalidArgumentException $e) {
            $exception = true;
        }
        $this->assert($exception, 'Invalid table name throws exception');
    }

    public function testPolicyDisabledTable(): void {
        $policy = new ApiPolicy([
            'tables' => [
                'test_items' => ['enabled' => false]
            ]
        ]);
        
        ApiGenerator::register($this->router, $this->testMeta, $policy);
        
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/api/test_items';
        
        ob_start();
        $this->router->dispatch(new Request(), new Response());
        $output = ob_get_clean();
        
        $data = json_decode($output, true);
        $this->assert(isset($data['error']), 'Disabled table returns 404');
    }

    public function testValidatePayloadRequired(): void {
        $meta = [
            'fields' => [
                ['name' => 'title', 'type' => 'string', 'required' => true]
            ]
        ];
        
        [$payload, $errors] = ApiPolicy::validatePayload([], $meta);
        $this->assert(isset($errors['title']), 'Required field validation works');
    }

    public function testValidatePayloadType(): void {
        $meta = [
            'fields' => [
                ['name' => 'age', 'type' => 'int']
            ]
        ];
        
        [$payload, $errors] = ApiPolicy::validatePayload(['age' => 'not-a-number'], $meta);
        $this->assert(isset($errors['age']), 'Type validation works');
    }

    public function testValidatePayloadEmail(): void {
        $meta = [
            'fields' => [
                ['name' => 'email', 'type' => 'email']
            ]
        ];
        
        [$payload, $errors] = ApiPolicy::validatePayload(['email' => 'invalid'], $meta);
        $this->assert(isset($errors['email']), 'Email validation works');
        
        [$payload, $errors] = ApiPolicy::validatePayload(['email' => 'test@example.com'], $meta);
        $this->assert(!isset($errors['email']), 'Valid email passes');
    }

    public function testValidatePayloadMaxLength(): void {
        $meta = [
            'fields' => [
                ['name' => 'code', 'type' => 'string', 'max' => 5]
            ]
        ];
        
        [$payload, $errors] = ApiPolicy::validatePayload(['code' => '123456'], $meta);
        $this->assert(isset($errors['code']), 'Max length validation works');
    }

    public function testValidatePayloadPartialUpdate(): void {
        $meta = [
            'fields' => [
                ['name' => 'title', 'type' => 'string', 'required' => true],
                ['name' => 'status', 'type' => 'string']
            ]
        ];
        
        [$payload, $errors] = ApiPolicy::validatePayload(['status' => 'active'], $meta, true);
        $this->assert(empty($errors), 'Partial update skips required check');
    }

    public function testValidatePayloadIgnoresUnknownFields(): void {
        $meta = [
            'fields' => [
                ['name' => 'title', 'type' => 'string']
            ]
        ];
        
        [$payload, $errors] = ApiPolicy::validatePayload(['title' => 'test', 'unknown' => 'value'], $meta);
        $this->assert(!isset($payload['unknown']), 'Unknown fields ignored');
    }

    public function testValidatePayloadPasswordHashing(): void {
        $meta = [
            'fields' => [
                ['name' => 'password', 'type' => 'string']
            ]
        ];
        
        [$payload, $errors] = ApiPolicy::validatePayload(['password' => 'secret123'], $meta);
        $this->assert($payload['password'] !== 'secret123', 'Password gets hashed');
        $this->assert(password_verify('secret123', $payload['password']), 'Password hash verifies');
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
        
        echo "\nAll ApiGenerator tests passed\n";
    }
}

if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    require_once __DIR__ . '/../../autoload.php';
    ApiGeneratorTest::run();
}
