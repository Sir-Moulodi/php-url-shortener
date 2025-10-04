<?php
// Written by Amir Hossin Moulodi
require 'config.php';

if (empty($_GET['code'])) {
    header("Location: index.php");
    exit;
}

$short_code = $_GET['code'];

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("SELECT long_url FROM urls WHERE short_code = ?");
    $stmt->execute([$short_code]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        header("Location: " . $result['long_url']);
        exit;
    } else {
        header("Location: index.php");
        exit;
    }
} catch (PDOException $e) {
    header("Location: index.php");
    exit;
}