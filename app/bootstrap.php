<?php
declare(strict_types=1);

date_default_timezone_set('America/Sao_Paulo');
$config = require dirname(__DIR__) . '/config/banco.php';
session_name('sessao_oficina_rota');
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off', 'path' => '/']);
ini_set('session.use_strict_mode', '1');
session_start();
header('Content-Type: text/html; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header("Content-Security-Policy: default-src 'self'; style-src 'self'; script-src 'self'; img-src 'self' data:; form-action 'self'; frame-ancestors 'none'; base-uri 'self'");
header('Cache-Control: no-store');
require __DIR__ . '/Suporte.php';
require __DIR__ . '/models/Oficina.php';
try {
    $pdo = new PDO("mysql:host={$config['host']};port={$config['porta']};dbname={$config['nome']};charset=utf8mb4", $config['usuario'], $config['senha'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]);
    $pdo->exec("SET time_zone = '-03:00'");
    $oficina = new Oficina($pdo);
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(503);
    exit('Não foi possível conectar ao banco. Confira a senha em config/local.php, a conexão com o MySQL Server e a importação pelo Workbench descrita no README.');
}
$_SESSION['csrf'] ??= bin2hex(random_bytes(32));
$usuario = null;
if (isset($_SESSION['usuario_id'])) {
    $usuario = $oficina->um('SELECT id, nome, email, perfil FROM usuarios WHERE id = ? AND ativo = 1', [$_SESSION['usuario_id']]);
    if (!$usuario) {
        unset($_SESSION['usuario_id']);
    }
}
