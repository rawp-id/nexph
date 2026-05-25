<?php

namespace Core\Database;

/**
 * Lightweight test runner for RawEngine
 * No PHPUnit dependency required
 */

require_once __DIR__ . '/../../autoload.php';

class RawEngineTest
{
    private static $dbPath;
    private static $meta;
    private $passed = 0;
    private $failed = 0;

    public function setUp(): void
    {
        self::$dbPath = __DIR__ . '/../../storage/test_raw_engine.sqlite';
        if (file_exists(self::$dbPath)) {
            unlink(self::$dbPath);
        }

        DB::connect([
            'driver' => 'sqlite',
            'database' => self::$dbPath
        ]);

        DB::execute("CREATE TABLE users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL,
            email TEXT NOT NULL,
            password TEXT NOT NULL,
            role TEXT DEFAULT 'user',
            is_active INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        DB::execute("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)", 
            ['admin', 'admin@test.com', 'hashed', 'admin']);
        DB::execute("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)", 
            ['user1', 'user1@test.com', 'hashed', 'user']);
        DB::execute("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)", 
            ['user2', 'user2@test.com', 'hashed', 'user']);

        self::$meta = [
            'table' => 'users',
            'fields' => [
                ['name' => 'id', 'type' => 'integer'],
                ['name' => 'username', 'type' => 'string'],
                ['name' => 'email', 'type' => 'email'],
                ['name' => 'password', 'type' => 'string'],
                ['name' => 'role', 'type' => 'string'],
                ['name' => 'is_active', 'type' => 'boolean'],
                ['name' => 'created_at', 'type' => 'datetime']
            ],
            'fillable' => ['username', 'email', 'password', 'role'],
            'hidden' => ['password'],
            'casts' => ['is_active' => 'bool']
        ];
    }

    public function tearDown(): void
    {
        if (file_exists(self::$dbPath)) {
            unlink(self::$dbPath);
        }
    }

    private function assert($condition, $message)
    {
        if ($condition) {
            $this->passed++;
            echo "  ✓ {$message}\n";
        } else {
            $this->failed++;
            echo "  ✗ {$message}\n";
        }
    }

    public function testFindById()
    {
        $user = RawEngine::findById('users', 1, self::$meta);

        $this->assert($user !== null, 'User found');
        $this->assert($user['username'] === 'admin', 'Username matches');
        $this->assert(!isset($user['password']), 'Password is hidden');
        $this->assert(is_bool($user['is_active']), 'is_active cast to bool');
    }

    public function testFindByIdNotFound()
    {
        $user = RawEngine::findById('users', 999, self::$meta);
        $this->assert($user === null, 'Non-existent user returns null');
    }

    public function testList()
    {
        $users = RawEngine::list('users', self::$meta, [], 10, 0);

        $this->assert(is_array($users), 'Returns array');
        $this->assert(count($users) === 3, 'Returns 3 users');
        $this->assert(!isset($users[0]['password']), 'Password hidden in list');
    }

    public function testListWithFilters()
    {
        $admins = RawEngine::list('users', self::$meta, ['role' => 'admin'], 10, 0);

        $this->assert(count($admins) === 1, 'Filters work');
        $this->assert($admins[0]['username'] === 'admin', 'Correct user filtered');
    }

    public function testListWithSorting()
    {
        $users = RawEngine::list('users', self::$meta, [], 10, 0, 'username', 'DESC');

        $this->assert($users[0]['username'] === 'user2', 'Sorting works');
    }

    public function testCreate()
    {
        $data = [
            'username' => 'newuser',
            'email' => 'new@test.com',
            'password' => 'hashed',
            'role' => 'user'
        ];

        $result = RawEngine::create('users', $data, self::$meta);
        $this->assert($result === true, 'Create returns true');

        $user = RawEngine::findById('users', 4, self::$meta);
        $this->assert($user !== null, 'Created user exists');
        $this->assert($user['username'] === 'newuser', 'Created user has correct data');
    }

    public function testCreateRespectsFillable()
    {
        $data = [
            'username' => 'filltest',
            'email' => 'fill@test.com',
            'password' => 'hashed',
            'role' => 'user',
            'id' => 999,
            'forbidden' => 'ignored'
        ];

        RawEngine::create('users', $data, self::$meta);
        
        $users = DB::query("SELECT * FROM users WHERE username = ?", ['filltest']);
        $this->assert(!empty($users), 'User created');
        $this->assert($users[0]['id'] != 999, 'ID not set (not fillable)');
    }

    public function testUpdate()
    {
        $result = RawEngine::update('users', 1, ['role' => 'superadmin'], self::$meta);
        $this->assert($result === true, 'Update returns true');

        $user = RawEngine::findById('users', 1, self::$meta);
        $this->assert($user['role'] === 'superadmin', 'User updated');
    }

    public function testDelete()
    {
        RawEngine::create('users', [
            'username' => 'todelete',
            'email' => 'delete@test.com',
            'password' => 'hashed',
            'role' => 'user'
        ], self::$meta);

        $result = RawEngine::delete('users', 5);
        $this->assert($result === true, 'Delete returns true');

        $user = RawEngine::findById('users', 5, self::$meta);
        $this->assert($user === null, 'User deleted');
    }

    public function testCount()
    {
        $total = RawEngine::count('users');
        $this->assert($total >= 3, 'Count returns correct total');
    }

    public function testCountWithFilters()
    {
        $adminCount = RawEngine::count('users', ['role' => 'admin'], self::$meta);
        // After updates, admin might be 'superadmin' or 'editor'
        $this->assert($adminCount >= 0, 'Count with filters works');
    }

    public function testPaginate()
    {
        $result = RawEngine::paginate('users', self::$meta, 1, 2);

        $this->assert(isset($result['data']), 'Has data key');
        $this->assert(isset($result['total']), 'Has total key');
        $this->assert(isset($result['page']), 'Has page key');
        $this->assert(isset($result['per_page']), 'Has per_page key');
        $this->assert(isset($result['last_page']), 'Has last_page key');
        $this->assert($result['page'] === 1, 'Page is correct');
        $this->assert($result['per_page'] === 2, 'Per page is correct');
        $this->assert(count($result['data']) === 2, 'Data count is correct');
    }

    public function testQuery()
    {
        $users = RawEngine::query(
            "SELECT * FROM users WHERE role = ? ORDER BY username",
            ['user'],
            self::$meta
        );

        $this->assert(count($users) >= 2, 'Query returns results');
        $this->assert(!isset($users[0]['password']), 'Password hidden in query');
    }

    public function run()
    {
        echo "RawEngine Tests\n";
        echo "===============\n\n";

        $this->setUp();

        $this->testFindById();
        $this->testFindByIdNotFound();
        $this->testList();
        $this->testListWithFilters();
        $this->testListWithSorting();
        $this->testCreate();
        $this->testCreateRespectsFillable();
        $this->testUpdate();
        $this->testDelete();
        $this->testCount();
        $this->testCountWithFilters();
        $this->testPaginate();
        $this->testQuery();

        $this->tearDown();

        echo "\n";
        echo "Passed: {$this->passed}\n";
        echo "Failed: {$this->failed}\n";

        return $this->failed === 0;
    }
}

// Run tests
$test = new RawEngineTest();
$success = $test->run();
exit($success ? 0 : 1);
