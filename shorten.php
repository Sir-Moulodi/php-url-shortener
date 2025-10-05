<?php
// Written by Amir Hossin Moulodi

require 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['long_url'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

$inputUrl = trim($_POST['long_url']);

if (!filter_var($inputUrl, FILTER_VALIDATE_URL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid URL.']);
    exit;
}

// Compute a stable hash for deduplication (binary 32 bytes for SHA-256)
$urlHashBinary = hash('sha256', $inputUrl, true);
$apcuHashKey = 'hash:' . bin2hex($urlHashBinary);

// Fast path via APCu
if (function_exists('apcu_fetch')) {
    $cachedCode = apcu_fetch($apcuHashKey, $hit);
    if ($hit && is_string($cachedCode) && $cachedCode !== '') {
        echo json_encode(['success' => true, 'short_code' => $cachedCode], JSON_UNESCAPED_SLASHES);
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

    // 1) Check by hash (fast, indexed)
    $findStmt = $pdo->prepare('SELECT short_code FROM urls WHERE long_url_hash = ? LIMIT 1');
    $findStmt->execute([$urlHashBinary]);
    $existing = $findStmt->fetch();

    if ($existing && isset($existing['short_code'])) {
        $shortCode = $existing['short_code'];
        if (function_exists('apcu_store')) {
            apcu_store($apcuHashKey, $shortCode, 86400);
            apcu_store('short:' . $shortCode, $inputUrl, 86400);
        }
        echo json_encode(['success' => true, 'short_code' => $shortCode], JSON_UNESCAPED_SLASHES);
        exit;
    }

    // 2) Generate a new short code and insert
    $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $alphabetLength = strlen($alphabet);

    $insertStmt = $pdo->prepare('INSERT INTO urls (long_url, long_url_hash, short_code) VALUES (?, ?, ?)');

    for ($attempt = 0; $attempt < 8; $attempt++) {
        // Generate cryptographically secure 6-char base62 code
        $shortCodeChars = [];
        for ($i = 0; $i < 6; $i++) {
            $index = random_int(0, $alphabetLength - 1);
            $shortCodeChars[] = $alphabet[$index];
        }
        $shortCode = implode('', $shortCodeChars);

        try {
            $insertStmt->execute([$inputUrl, $urlHashBinary, $shortCode]);

            if (function_exists('apcu_store')) {
                apcu_store($apcuHashKey, $shortCode, 86400);
                apcu_store('short:' . $shortCode, $inputUrl, 86400);
            }
            echo json_encode(['success' => true, 'short_code' => $shortCode], JSON_UNESCAPED_SLASHES);
            exit;
        } catch (PDOException $e) {
            $driverCode = $e->errorInfo[1] ?? null; // MySQL-specific error code
            $message = $e->getMessage();
            if ($driverCode === 1062) { // duplicate key
                // If duplicate due to long_url_hash, another request won the race; fetch and return
                if (strpos($message, 'long_url_hash') !== false) {
                    $findStmt->execute([$urlHashBinary]);
                    $row = $findStmt->fetch();
                    if ($row && isset($row['short_code'])) {
                        $shortCode = $row['short_code'];
                        if (function_exists('apcu_store')) {
                            apcu_store($apcuHashKey, $shortCode, 86400);
                            apcu_store('short:' . $shortCode, $inputUrl, 86400);
                        }
                        echo json_encode(['success' => true, 'short_code' => $shortCode], JSON_UNESCAPED_SLASHES);
                        exit;
                    }
                }
                // If duplicate due to short_code collision, retry with a new code
                continue;
            }
            throw $e; // Other DB error
        }
    }

    echo json_encode(['success' => false, 'message' => 'Generation conflict, please retry.']);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}