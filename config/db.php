<?php
$host = 'localhost';
$dbname = 'sports_app';
$username = 'root';
$password = '';

require_once __DIR__ . '/../vendor/autoload.php'; // If using composer
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();
define('ENCRYPTION_KEY', getenv('ENCRYPTION_KEY'));

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>