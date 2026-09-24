<?php
declare(strict_types=1);

/**
 * Satu koneksi PDO yang dipakai ulang di seluruh request.
 * Semua query memakai prepared statement, jadi aman dari SQL injection.
 */
function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        $pdo->exec("SET time_zone = '+07:00'"); // samakan dengan Asia/Jakarta
    } catch (PDOException $e) {
        http_response_code(500);
        error_log('Koneksi database gagal: ' . $e->getMessage());
        exit('Koneksi database gagal. Pastikan MySQL menyala, file database/fluffy_chicken.sql sudah diimport, '
            . 'dan pengaturan di includes/config.php sudah benar.');
    }

    return $pdo;
}
