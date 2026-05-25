<?php
namespace Tests\Unit;

use Core\Http\Router;
use Core\Http\Request;
use Core\Http\Response;

class RouterTest {
    private Router $router;
    private array $output = [];

    public function setUp(): void {
        $this->router = new Router();
        $this->output = [];
    }

    public function testAddRoute(): void {
        $this->router->add('GET', '/test', function($req, $res) {
            $res->json(['ok' => true]);
        });
        $this->assert(true, 'Route added');
    }

    public function testDispatchSimpleRoute(): void {
        $this->router->add('GET', '/hello', function($req, $res) {
            $res->json(['message' => 'hello']);
        });
        
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/hello';
        
        ob_start();
        $this->router->dispatch(new Request(), new Response());
        $output = ob_get_clean();
        
        $data = json_decode($output, true);
        $this->assert($data['message'] === 'hello', 'Simple route works');
    }

    public function testDispatchWithParams(): void {
        $this->router->add('GET', '/user/{id}', function($req, $res, $params) {
            $res->json(['id' => $params['id']]);
        });
        
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/user/123';
        
        ob_start();
        $this->router->dispatch(new Request(), new Response());
        $output = ob_get_clean();
        
        $data = json_decode($output, true);
        $this->assert($data['id'] === '123', 'Route params work');
    }

    public function testDispatchWithMultipleParams(): void {
        $this->router->add('GET', '/post/{id}/comment/{cid}', function($req, $res, $params) {
            $res->json(['id' => $params['id'], 'cid' => $params['cid']]);
        });
        
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/post/42/comment/99';
        
        ob_start();
        $this->router->dispatch(new Request(), new Response());
        $output = ob_get_clean();
        
        $data = json_decode($output, true);
        $this->assert($data['id'] === '42' && $data['cid'] === '99', 'Multiple params work');
    }

    public function testDispatchNotFound(): void {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/notfound';
        
        ob_start();
        $this->router->dispatch(new Request(), new Response());
        $output = ob_get_clean();
        
        $data = json_decode($output, true);
        $this->assert(isset($data['error']), '404 returns error');
    }

    public function testMiddleware(): void {
        $executed = false;
        $middleware = function($req, $res, $params) use (&$executed) {
            $executed = true;
        };
        
        $this->router->add('GET', '/protected', function($req, $res) {
            $res->json(['ok' => true]);
        }, [$middleware]);
        
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/protected';
        
        ob_start();
        $this->router->dispatch(new Request(), new Response());
        ob_get_clean();
        
        $this->assert($executed === true, 'Middleware executes');
    }

    public function testMethodMatching(): void {
        $this->router->add('POST', '/create', function($req, $res) {
            $res->json(['created' => true]);
        });
        
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/create';
        
        ob_start();
        $this->router->dispatch(new Request(), new Response());
        $output = ob_get_clean();
        
        $data = json_decode($output, true);
        $this->assert(isset($data['error']), 'Method mismatch returns 404');
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
        
        echo "\nAll Router tests passed\n";
    }
}

if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    require_once __DIR__ . '/../../autoload.php';
    RouterTest::run();
}
