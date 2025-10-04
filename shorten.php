<?php
// Written by Amir Hossin Moulodi

require 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['long_url'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

$long_url = $_POST['long_url'];

if (!filter_var($long_url, FILTER_VALIDATE_URL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid URL.']);
    exit;
}

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("SELECT short_code FROM urls WHERE long_url = ?");
    $stmt->execute([$long_url]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        echo json_encode(['success' => true, 'short_code' => $existing['short_code']]);
        exit;
    }

    do {
        $short_code = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 6);
        $stmt = $pdo->prepare("SELECT short_code FROM urls WHERE short_code = ?");
        $stmt->execute([$short_code]);
    } while ($stmt->fetch());

    $stmt = $pdo->prepare("INSERT INTO urls (long_url, short_code) VALUES (?, ?)");
    $stmt->execute([$long_url, $short_code]);

    echo json_encode(['success' => true, 'short_code' => $short_code]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}