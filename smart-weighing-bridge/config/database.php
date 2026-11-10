<?php
/**
 * Konfigurasi database menggunakan PDO
 * Smart Weighing Bridge
 */

namespace Config;

use PDO;
use PDOException;

class Database
{
    private $host = 'localhost';
    private $dbname = 'smart_weighing_bridge';
    private $username = 'root';
    private $password = '';
    private $charset = 'utf8mb4';
    private $pdo = null;

    public function getConnection(): ?PDO
    {
        if ($this->pdo !== null) {
            return $this->pdo;
        }

        $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
        ];

        try {
            $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            // Jangan bocorkan detail error database ke klien
            error_log("Database connection error: " . $e->getMessage());
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'message' => 'Koneksi database gagal. Silakan hubungi administrator.'
            ]);
            exit;
        }

        return $this->pdo;
    }

    public function close(): void
    {
        $this->pdo = null;
    }
}