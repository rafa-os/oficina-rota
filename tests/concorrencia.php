<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}
$config = require dirname(__DIR__) . '/config/banco.php';
if (!preg_match('/^[a-z0-9_]+_teste$/', $config['nome'])) {
    throw new RuntimeException('Este teste exige um banco terminado em _teste.');
}
require dirname(__DIR__) . '/app/Suporte.php';
require dirname(__DIR__) . '/app/models/Oficina.php';
$pdo = new PDO("mysql:host={$config['host']};port={$config['porta']};dbname={$config['nome']};charset=utf8mb4", $config['usuario'], $config['senha'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]);
$pdo->exec("SET time_zone = '-03:00'");
$dados = json_decode(stream_get_contents(STDIN), true, 512, JSON_THROW_ON_ERROR);
while (microtime(true) < $dados['iniciar_em']) {
    usleep(1000);
}
try {
    $oficina = new Oficina($pdo);
    $id = $oficina->salvarOrdem($dados['ordem'], ['id' => 1, 'perfil' => 'administrador']);
    echo json_encode(['status' => 200, 'id' => $id]);
} catch (RegraException $e) {
    echo json_encode(['status' => $e->status, 'mensagem' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
