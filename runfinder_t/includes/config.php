<?php
// includes/config.php
// Configurações de conexão e sessão

session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax'
]);

// Ajuste conforme seu ambiente
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'runfinder');
define('DB_USER', 'root');
define('DB_PASS', '');

// Uploads
define('UPLOAD_BASE', __DIR__ . '/../uploads');
define('UPLOAD_BLOGS', UPLOAD_BASE . '/blogs');

// Garante que diretórios existam (deve ter permissões adequadas)
if (!is_dir(UPLOAD_BASE)) mkdir(UPLOAD_BASE, 0755, true);
if (!is_dir(UPLOAD_BLOGS)) mkdir(UPLOAD_BLOGS, 0755, true);

// DSN
$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    die("Erro conexão DB: " . $e->getMessage());
}
?>