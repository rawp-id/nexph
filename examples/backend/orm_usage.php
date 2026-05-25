<?php

use Core\Database\Model;
use Core\Database\QueryBuilder;
use Core\Database\RawEngine;
use Core\Database\Metadata;

// Model: Ultra-thin convenience wrapper (optional)
$users = Model::table('users');
$user = $users->find(1);

$allUsers = $users->all();

$newUser = $users->create([
    'username' => 'john',
    'password' => password_hash('secret', PASSWORD_BCRYPT),
    'role' => 'admin'
]);

$updated = $users->update(1, ['role' => 'editor']);

$users->delete(1);

// QueryBuilder: Direct SQL building
$query = (new QueryBuilder('users'))
    ->where('role', '=', 'admin')
    ->orderBy('created_at', 'DESC')
    ->limit(10)
    ->get();

// RawEngine: High-performance (recommended for generated code)
$meta = Metadata::load('users');
$admins = RawEngine::list('users', $meta, ['role' => 'admin'], 10, 0);
$paginated = RawEngine::paginate('users', $meta, 1, 20);
