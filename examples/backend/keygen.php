<?php
$key = base64_encode(random_bytes(32));
$envFile = __DIR__ . '/.env';
$content = "APP_KEY=$key\n";

if (file_exists($envFile)) {
    $existing = file_get_contents($envFile);
    if (strpos($existing, 'APP_KEY=') !== false) {
        $content = preg_replace('/APP_KEY=.*/', "APP_KEY=$key", $existing);
    } else {
        $content = trim($existing) . "\n" . $content;
    }
}

file_put_contents($envFile, $content);
echo "Generated APP_KEY: $key\n";
echo "Saved to .env\n";
