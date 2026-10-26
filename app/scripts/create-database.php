<?php
// Only creates the configured schema when absent; never drops a database.
require __DIR__.'/../vendor/autoload.php';
Dotenv\Dotenv::createImmutable(dirname(__DIR__))->load();
$name=$_ENV['DB_DATABASE'] ?? 'forma_construction';
if (! preg_match('/^[A-Za-z0-9_]+$/',$name)) { fwrite(STDERR,"Database name must contain only letters, digits and underscores.\n"); exit(1); }
$host=$_ENV['DB_HOST'] ?? '127.0.0.1'; $port=(int)($_ENV['DB_PORT'] ?? 3306);
try {
    $pdo=new PDO("mysql:host=$host;port=$port;charset=utf8mb4",$_ENV['DB_USERNAME'] ?? 'root',$_ENV['DB_PASSWORD'] ?? '',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Configured MySQL schema is ready.\n";
} catch (PDOException $e) { fwrite(STDERR,"Could not create the database. Start MySQL in Laragon and check DB_* in .env, or create the schema manually with an authorized account.\n"); exit(1); }
