<?php

declare(strict_types=1);

$host = 'localhost';
$dbname = 'course_driver';
$username = 'root';
$password = '';

try {
    $pdo = new PDO(
        "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    error_log('Erreur de connexion à la base de données : ' . $e->getMessage());

    http_response_code(500);
    exit('Impossible de se connecter à la base de données. Vérifiez la configuration.');
}