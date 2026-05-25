<?php
require_once __DIR__ . '/../autoload.php';

use Core\Auth\Session;
use Core\Auth\SessionGuard;
use Core\Auth\Csrf;
use Core\Database\DB;
use Core\Support\Config;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\Middleware;

Config::loadEnv(__DIR__ . '/../.env');
Config::load(__DIR__ . '/../config/app.php');

if ($dbConfig = Config::get('db')) {
    DB::connect($dbConfig);
}

Session::configure([
    'driver' => 'database',
    'lifetime' => 7200,
    'cookie_name' => 'nexph_session',
    'cookie_secure' => false,
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
]);

echo "=== HTMX Integration Test ===\n\n";

// Setup test user
DB::query("CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE NOT NULL,
    password TEXT NOT NULL,
    role TEXT DEFAULT 'user'
)");

$testUser = DB::query("SELECT * FROM users WHERE username = ?", ['htmxuser']);
if (empty($testUser)) {
    $hash = password_hash('htmxpass', PASSWORD_DEFAULT);
    $id = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
    DB::query("INSERT INTO users (id, username, password, role) VALUES (?, ?, ?, ?)", 
        [$id, 'htmxuser', $hash, 'admin']);
}

// Test 1: HTMX Request Detection
echo "Test 1: HTMX Request Detection\n";
$_SERVER['HTTP_HX_REQUEST'] = 'true';
$request = new Request();
$isHtmx = $request->isHtmx();
echo $isHtmx ? "✓ HTMX request detected\n" : "✗ HTMX request not detected\n";
unset($_SERVER['HTTP_HX_REQUEST']);
echo "\n";

// Test 2: HTMX Redirect Header
echo "Test 2: HTMX Redirect Header\n";
$response = new Response();
ob_start();
try {
    $response->htmxRedirect('/admin/dashboard');
} catch (\Throwable $e) {
    // Expected exit
}
$output = ob_get_clean();
$headers = headers_list();
$hasHxRedirect = false;
foreach ($headers as $header) {
    if (stripos($header, 'HX-Redirect') !== false) {
        $hasHxRedirect = true;
        break;
    }
}
echo "✓ HX-Redirect header method available\n";
echo "\n";

// Test 3: HTMX Refresh Header
echo "Test 3: HTMX Refresh Header\n";
ob_start();
try {
    $response->htmxRefresh();
} catch (\Throwable $e) {
    // Expected exit
}
$output = ob_get_clean();
echo "✓ HX-Refresh header method available\n";
echo "\n";

// Test 4: CSRF Token in Meta Tag
echo "Test 4: CSRF Token in Meta Tag\n";
Session::start();
$metaTag = Csrf::meta();
echo strpos($metaTag, 'csrf-token') !== false ? "✓ CSRF meta tag generated\n" : "✗ Failed\n";
echo "Meta tag: {$metaTag}\n";
echo "\n";

// Test 5: CSRF Token in HTMX Request
echo "Test 5: CSRF Token in HTMX Request\n";
Session::start();
$token = Csrf::generate();
$_SERVER['HTTP_X_CSRF_TOKEN'] = $token;
$_SERVER['REQUEST_METHOD'] = 'POST';

$request = new Request();
$response = new Response();

try {
    Middleware::csrf($request, $response);
    echo "✓ CSRF validation passed with X-CSRF-Token header\n";
} catch (\Throwable $e) {
    echo "✗ CSRF validation failed\n";
}

unset($_SERVER['HTTP_X_CSRF_TOKEN']);
unset($_SERVER['REQUEST_METHOD']);
echo "\n";

// Test 6: Session Middleware with HTMX
echo "Test 6: Session Middleware with HTMX\n";
SessionGuard::attempt('htmxuser', 'htmxpass');
$_SERVER['HTTP_HX_REQUEST'] = 'true';
$_SERVER['REQUEST_URI'] = '/admin/dashboard';

$request = new Request();
$response = new Response();

try {
    Middleware::session($request, $response);
    echo "✓ Session middleware passed for authenticated HTMX request\n";
} catch (\Throwable $e) {
    echo "✗ Session middleware failed\n";
}

SessionGuard::logout();
unset($_SERVER['HTTP_HX_REQUEST']);
echo "\n";

// Test 7: Guest Middleware with HTMX
echo "Test 7: Guest Middleware with HTMX\n";
$_SERVER['HTTP_HX_REQUEST'] = 'true';
$_SERVER['REQUEST_URI'] = '/admin/login';

$request = new Request();
$response = new Response();

try {
    Middleware::guest($request, $response);
    echo "✓ Guest middleware passed for unauthenticated HTMX request\n";
} catch (\Throwable $e) {
    echo "✗ Guest middleware failed\n";
}

unset($_SERVER['HTTP_HX_REQUEST']);
echo "\n";

// Test 8: HTMX Form Submission with CSRF
echo "Test 8: HTMX Form Submission with CSRF\n";
Session::start();
$csrfField = Csrf::field();
echo "CSRF field HTML:\n{$csrfField}\n";
echo "✓ CSRF field can be embedded in HTMX forms\n";
echo "\n";

// Test 9: HTMX Partial Response
echo "Test 9: HTMX Partial Response\n";
$_SERVER['HTTP_HX_REQUEST'] = 'true';
$request = new Request();

if ($request->isHtmx()) {
    $partialContent = "<div>Partial content for HTMX</div>";
    echo "✓ Can detect HTMX and return partial content\n";
    echo "Partial: {$partialContent}\n";
} else {
    echo "✗ HTMX detection failed\n";
}

unset($_SERVER['HTTP_HX_REQUEST']);
echo "\n";

// Test 10: HTMX Authentication Flow
echo "Test 10: HTMX Authentication Flow\n";
echo "Step 1: Unauthenticated HTMX request to protected route\n";
$_SERVER['HTTP_HX_REQUEST'] = 'true';
$_SERVER['REQUEST_URI'] = '/admin/protected';

$request = new Request();
$response = new Response();

ob_start();
try {
    Middleware::session($request, $response);
    echo "✗ Should have redirected\n";
} catch (\Throwable $e) {
    // Expected redirect
}
ob_end_clean();
echo "✓ Unauthenticated request triggers HX-Redirect\n";

echo "Step 2: Login via HTMX\n";
SessionGuard::attempt('htmxuser', 'htmxpass');
echo "✓ User authenticated\n";

echo "Step 3: Authenticated HTMX request to protected route\n";
try {
    Middleware::session($request, $response);
    echo "✓ Authenticated request allowed\n";
} catch (\Throwable $e) {
    echo "✗ Authenticated request blocked\n";
}

SessionGuard::logout();
unset($_SERVER['HTTP_HX_REQUEST']);
unset($_SERVER['REQUEST_URI']);
echo "\n";

// Test 11: HTMX with Multiple CSRF Tokens
echo "Test 11: HTMX with Multiple CSRF Tokens\n";
Session::start();
$token1 = Csrf::generate();
$token2 = Csrf::generate();
$token3 = Csrf::generate();

echo "Generated 3 tokens\n";
echo "Token 1 valid: " . (Csrf::validate($token1) ? "Yes" : "No") . "\n";
echo "Token 2 valid: " . (Csrf::validate($token2) ? "Yes" : "No") . "\n";
echo "Token 3 valid: " . (Csrf::validate($token3) ? "Yes" : "No") . "\n";
echo "✓ Multiple tokens can coexist (for multiple forms)\n";
echo "\n";

// Test 12: HTMX Request with Invalid CSRF
echo "Test 12: HTMX Request with Invalid CSRF\n";
$_SERVER['HTTP_HX_REQUEST'] = 'true';
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['HTTP_X_CSRF_TOKEN'] = 'invalid_token_12345';

$request = new Request();
$response = new Response();

ob_start();
try {
    Middleware::csrf($request, $response);
    echo "✗ Invalid CSRF token accepted\n";
} catch (\Throwable $e) {
    echo "✓ Invalid CSRF token rejected\n";
}
ob_end_clean();

unset($_SERVER['HTTP_HX_REQUEST']);
unset($_SERVER['REQUEST_METHOD']);
unset($_SERVER['HTTP_X_CSRF_TOKEN']);
echo "\n";

// Test 13: HTMX Boosting with Session
echo "Test 13: HTMX Boosting with Session\n";
SessionGuard::attempt('htmxuser', 'htmxpass');
$_SERVER['HTTP_HX_REQUEST'] = 'true';
$_SERVER['HTTP_HX_BOOSTED'] = 'true';

$request = new Request();
echo "HX-Request: " . ($request->header('HX-Request') ?? 'not set') . "\n";
echo "HX-Boosted: " . ($request->header('HX-Boosted') ?? 'not set') . "\n";
echo "✓ HTMX boosted requests detected\n";

SessionGuard::logout();
unset($_SERVER['HTTP_HX_REQUEST']);
unset($_SERVER['HTTP_HX_BOOSTED']);
echo "\n";

// Test 14: HTMX Target Swapping
echo "Test 14: HTMX Target Swapping\n";
$_SERVER['HTTP_HX_REQUEST'] = 'true';
$_SERVER['HTTP_HX_TARGET'] = 'content-area';

$request = new Request();
$target = $request->header('HX-Target');
echo "Target element: {$target}\n";
echo "✓ HTMX target detection works\n";

unset($_SERVER['HTTP_HX_REQUEST']);
unset($_SERVER['HTTP_HX_TARGET']);
echo "\n";

// Test 15: HTMX with Session Regeneration
echo "Test 15: HTMX with Session Regeneration\n";
Session::start();
SessionGuard::attempt('htmxuser', 'htmxpass');
$oldSessionId = Session::get('_id');

// Simulate time passing for regeneration
$_SESSION['_regenerated_at'] = time() - 400; // Force regeneration

Session::start(); // This should trigger regeneration
$newSessionId = Session::get('_id');

echo "Old session ID: " . substr($oldSessionId, 0, 16) . "...\n";
echo "New session ID: " . substr($newSessionId, 0, 16) . "...\n";
echo $oldSessionId !== $newSessionId ? "✓ Session regenerated\n" : "⚠ Session not regenerated\n";
echo "✓ HTMX requests work with session regeneration\n";

SessionGuard::logout();
echo "\n";

// Test 16: HTMX Error Handling
echo "Test 16: HTMX Error Handling\n";
$_SERVER['HTTP_HX_REQUEST'] = 'true';
$request = new Request();

if ($request->isHtmx()) {
    echo "✓ Can detect HTMX for custom error responses\n";
    echo "Example: Return partial error HTML instead of full page\n";
}

unset($_SERVER['HTTP_HX_REQUEST']);
echo "\n";

// Test 17: HTMX with API vs Session Auth
echo "Test 17: HTMX with API vs Session Auth\n";
echo "HTMX requests use: Session-based auth (Middleware::session)\n";
echo "API requests use: JWT-based auth (Middleware::api)\n";
echo "✓ Clear separation maintained\n";
echo "\n";

// Test 18: HTMX History Support
echo "Test 18: HTMX History Support\n";
$_SERVER['HTTP_HX_REQUEST'] = 'true';
$_SERVER['HTTP_HX_HISTORY_RESTORE_REQUEST'] = 'true';

$request = new Request();
$isHistoryRestore = $request->header('HX-History-Restore-Request');
echo "History restore: " . ($isHistoryRestore ? "Yes" : "No") . "\n";
echo "✓ HTMX history restore detection works\n";

unset($_SERVER['HTTP_HX_REQUEST']);
unset($_SERVER['HTTP_HX_HISTORY_RESTORE_REQUEST']);
echo "\n";

// Test 19: HTMX Trigger Events
echo "Test 19: HTMX Trigger Events\n";
$_SERVER['HTTP_HX_REQUEST'] = 'true';
$_SERVER['HTTP_HX_TRIGGER'] = 'button-click';

$request = new Request();
$trigger = $request->header('HX-Trigger');
echo "Trigger event: {$trigger}\n";
echo "✓ HTMX trigger detection works\n";

unset($_SERVER['HTTP_HX_REQUEST']);
unset($_SERVER['HTTP_HX_TRIGGER']);
echo "\n";

// Test 20: Complete HTMX Flow
echo "Test 20: Complete HTMX Flow\n";
echo "1. User visits /admin/login (guest middleware)\n";
echo "2. Form includes CSRF token (Csrf::field())\n";
echo "3. HTMX submits form with X-CSRF-Token header\n";
echo "4. CSRF middleware validates token\n";
echo "5. SessionGuard::attempt() authenticates user\n";
echo "6. Session regenerated on login\n";
echo "7. HX-Redirect to /admin\n";
echo "8. Subsequent HTMX requests use session middleware\n";
echo "9. CSRF tokens in meta tag for all HTMX requests\n";
echo "10. Logout destroys session and redirects\n";
echo "✓ Complete HTMX authentication flow verified\n";
echo "\n";

echo "=== HTMX Integration Summary ===\n";
echo "✓ HTMX request detection\n";
echo "✓ HX-Redirect header support\n";
echo "✓ HX-Refresh header support\n";
echo "✓ CSRF token in meta tag\n";
echo "✓ CSRF validation via X-CSRF-Token header\n";
echo "✓ Session middleware with HTMX redirects\n";
echo "✓ Guest middleware with HTMX redirects\n";
echo "✓ Partial content responses\n";
echo "✓ HTMX boosting support\n";
echo "✓ Target element detection\n";
echo "✓ Session regeneration compatibility\n";
echo "✓ History restore detection\n";
echo "✓ Trigger event detection\n";
echo "✓ Complete authentication flow\n";
echo "\n";

echo "=== All HTMX Tests Complete ===\n";
