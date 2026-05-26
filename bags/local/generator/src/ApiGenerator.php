<?php
namespace Core\Generator;

use Core\Http\Request;
use Core\Http\Response;
use Core\Http\Router;
use Core\Http\ApiPolicy;
use Core\Database\RawEngine;
use Core\Database\FieldControl;

class ApiGenerator {
    public static function register(Router $router, array $meta, ?ApiPolicy $policy = null): void {
        $policy ??= new ApiPolicy();
        $table = $meta['table'];

        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $table)) {
            throw new \InvalidArgumentException("Invalid table name: {$table}");
        }
        if (!$policy->isTableEnabled($table)) {
            return;
        }
        $basePath = "/api/{$table}";

        $router->add('GET', $basePath, function(Request $req, Response $res) use ($table, $meta, $policy) {
            $params = $req->input();
            $limit = max(1, min($policy->maxLimit($table), (int)($params['limit'] ?? 20)));
            $offset = max(0, (int)($params['offset'] ?? 0));
            
            $validFields = array_column($meta['fields'], 'name');
            $validFields[] = 'id';
            $filters = [];
            foreach ($params as $key => $val) {
                if (in_array($key, ['limit', 'offset', 'sort', 'order'], true)) {
                    continue;
                }
                if (in_array($key, $validFields, true) && is_scalar($val)) {
                    $filters[$key] = $val;
                }
            }
            
            $orderBy = $params['sort'] ?? null;
            $orderDir = $params['order'] ?? 'ASC';
            
            $data = RawEngine::list($table, $meta, $filters, $limit, $offset, $orderBy, $orderDir);
            $res->json(['data' => $data]);
        }, $policy->middleware($table, 'list'));

        $router->add('GET', "{$basePath}/{id}", function(Request $req, Response $res, array $params) use ($table, $meta) {
            $data = RawEngine::findById($table, $params['id'], $meta);
            if (!$data) {
                $res->json(['error' => 'Not Found'], 404);
            }
            $res->json($data);
        }, $policy->middleware($table, 'detail'));

        $router->add('POST', $basePath, function(Request $req, Response $res) use ($table, $meta) {
            $input = $req->input();
            [$payload, $errors] = ApiPolicy::validatePayload($input, $meta);
            if ($errors) {
                $res->json(['error' => 'Validation failed', 'fields' => $errors], 422);
            }
            if (!$payload) {
                $res->json(['error' => 'No valid data provided'], 400);
            }

            if (!RawEngine::create($table, $payload, $meta)) {
                $res->json(['error' => 'Failed to create'], 500);
            }
            
            if ($req->header('HX-Request')) {
                header("HX-Redirect: /admin/{$table}");
                exit;
            }
            $res->json(['status' => 'created'], 201);
        }, $policy->middleware($table, 'create'));

        $router->add('PUT', "{$basePath}/{id}", function(Request $req, Response $res, array $params) use ($table, $meta) {
            $input = $req->input();
            [$payload, $errors] = ApiPolicy::validatePayload($input, $meta, true);
            if ($errors) {
                $res->json(['error' => 'Validation failed', 'fields' => $errors], 422);
            }
            if (!$payload) {
                $res->json(['error' => 'No valid data provided'], 400);
            }
            
            if (!RawEngine::update($table, $params['id'], $payload, $meta)) {
                $res->json(['error' => 'Failed to update'], 500);
            }
            $res->json(['status' => 'updated']);
        }, $policy->middleware($table, 'update'));

        $router->add('DELETE', "{$basePath}/{id}", function(Request $req, Response $res, array $params) use ($table) {
            if (!RawEngine::delete($table, $params['id'])) {
                $res->json(['error' => 'Failed to delete'], 500);
            }
            $res->json(['status' => 'deleted']);
        }, $policy->middleware($table, 'delete'));
    }


}
