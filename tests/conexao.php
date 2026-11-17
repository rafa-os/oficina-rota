<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}
$config = require dirname(__DIR__) . '/config/banco.php';
try {
    $db = new PDO("mysql:host={$config['host']};port={$config['porta']};dbname={$config['nome']};charset=utf8mb4", $config['usuario'], $config['senha'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    $servidor = $db->query('SELECT @@hostname AS servidor, @@port AS porta, VERSION() AS versao, @@version_comment AS distribuicao, DATABASE() AS banco, CURRENT_USER() AS usuario')->fetch();
    echo json_encode(['host_configurado' => $config['host'], 'porta_configurada' => $config['porta'], 'banco_configurado' => $config['nome'], 'conexao' => $servidor], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
} catch (PDOException $e) {
    fwrite(STDERR, 'Falha na conexão MySQL: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
