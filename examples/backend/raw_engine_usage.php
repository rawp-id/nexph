<?php

/**
 * Raw Engine Usage Examples
 * 
 * High-performance database operations for generated systems.
 * Use this approach in ApiGenerator, UiGenerator, and internal tools.
 */

require_once __DIR__ . '/../autoload.php';

use Core\Database\DB;
use Core\Database\RawEngine;
use Core\Database\Metadata;

// Connect to database
DB::connect([
    'driver' => 'sqlite',
    'database' => __DIR__ . '/../storage/database.sqlite'
]);

// Load metadata
$meta = Metadata::load('users');

echo "=== Raw Engine Examples ===\n\n";

// 1. Find by ID
echo "1. Find by ID:\n";
$user = RawEngine::findById('users', 1, $meta);
if ($user) {
    echo "   Found: {$user['username']}\n";
    echo "   Hidden fields (password) automatically removed\n";
    echo "   Casts automatically applied\n";
} else {
    echo "   User not found\n";
}
echo "\n";

// 2. List with filters
echo "2. List with filters:\n";
$admins = RawEngine::list('users', $meta, ['role' => 'admin'], 10, 0);
echo "   Found " . count($admins) . " admin users\n";
foreach ($admins as $admin) {
    echo "   - {$admin['username']} ({$admin['role']})\n";
}
echo "\n";

// 3. List with sorting
echo "3. List with sorting:\n";
$recent = RawEngine::list('users', $meta, [], 5, 0, 'created_at', 'DESC');
echo "   Found " . count($recent) . " recent users\n";
foreach ($recent as $user) {
    echo "   - {$user['username']} (created: {$user['created_at']})\n";
}
echo "\n";

// 4. Create record
echo "4. Create record:\n";
$newUser = [
    'username' => 'rawengine_test_' . time(),
    'password' => password_hash('secret', PASSWORD_BCRYPT),
    'email' => 'test@example.com',
    'role' => 'user'
];
$created = RawEngine::create('users', $newUser, $meta);
if ($created) {
    echo "   ✓ User created successfully\n";
    echo "   Fillable fields automatically filtered\n";
} else {
    echo "   ✗ Failed to create user\n";
}
echo "\n";

// 5. Update record
echo "5. Update record:\n";
$updated = RawEngine::update('users', 1, ['role' => 'editor'], $meta);
if ($updated) {
    echo "   ✓ User updated successfully\n";
    echo "   Fillable fields automatically filtered\n";
} else {
    echo "   ✗ Failed to update user\n";
}
echo "\n";

// 6. Count records
echo "6. Count records:\n";
$totalUsers = RawEngine::count('users');
echo "   Total users: {$totalUsers}\n";
$totalAdmins = RawEngine::count('users', ['role' => 'admin'], $meta);
echo "   Total admins: {$totalAdmins}\n";
echo "\n";

// 7. Paginate
echo "7. Paginate:\n";
$paginated = RawEngine::paginate('users', $meta, 1, 5);
echo "   Page: {$paginated['page']} of {$paginated['last_page']}\n";
echo "   Total: {$paginated['total']} records\n";
echo "   Per page: {$paginated['per_page']}\n";
echo "   Records on this page: " . count($paginated['data']) . "\n";
echo "\n";

// 8. Raw query with metadata transformations
echo "8. Raw query with metadata:\n";
$custom = RawEngine::query(
    "SELECT * FROM users WHERE role = ? ORDER BY created_at DESC LIMIT ?",
    ['admin', 3],
    $meta
);
echo "   Found " . count($custom) . " records\n";
echo "   Hidden fields removed, casts applied automatically\n";
echo "\n";

echo "=== Performance Characteristics ===\n\n";
echo "✓ No object hydration overhead\n";
echo "✓ Explicit SQL execution\n";
echo "✓ Automatic metadata transformations (fillable, hidden, casts)\n";
echo "✓ Easy to debug and profile\n";
echo "✓ Predictable performance\n";
echo "✓ No N+1 query issues\n";
echo "✓ No lazy loading surprises\n";
echo "\n";

echo "=== When to Use Raw Engine ===\n\n";
echo "✓ ApiGenerator (auto-generated CRUD)\n";
echo "✓ UiGenerator (dashboard tables)\n";
echo "✓ Schema management tools\n";
echo "✓ Migration scripts\n";
echo "✓ Internal engine operations\n";
echo "✓ Maximum performance required\n";
echo "✓ Simple CRUD operations\n";
echo "✗ Complex relations (use Model instead)\n";
echo "✗ Reusable query patterns (use Model instead)\n";
echo "\n";
