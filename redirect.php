<?php
// Written by Amir Hossin Moulodi
require 'config.php';

// Validate the short code early
if (empty($_GET['code']) || !preg_match('/^[A-Za-z0-9]{6}$/', $_GET['code'])) {
    header('Location: index.php', true, 302);
    exit;
}

$shortCode = $_GET['code'];
$cacheKey = 'short:' . $shortCode;

// APCu fast-path
if (function_exists('apcu_fetch')) {
    $cached = apcu_fetch($cacheKey, $cacheHit);
    if ($cacheHit && is_string($cached) && $cached !== '') {
        header('Cache-Control: public, max-age=31536000, immutable');
        header('Location: ' . $cached, true, 301);
        exit;
    }
}

try {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_PERSISTENT => true,
            ]
        );
    }

    $stmt = $pdo->prepare('SELECT long_url FROM urls WHERE short_code = ? LIMIT 1');
    $stmt->execute([$shortCode]);
    $row = $stmt->fetch();

    if ($row && isset($row['long_url'])) {
        $longUrl = $row['long_url'];
        if (function_exists('apcu_store')) {
            apcu_store($cacheKey, $longUrl, 86400); // cache mapping for 1 day
        }
        header('Cache-Control: public, max-age=31536000, immutable');
        header('Location: ' . $longUrl, true, 301);
        exit;
    }

    // Not found: return lightweight 404 to avoid extra redirect cost
    http_response_code(404);
    header('Cache-Control: no-store');
    echo 'Not found';
    exit;
} catch (Throwable $e) {
    header('Location: index.php', true, 302);
    exit;
}