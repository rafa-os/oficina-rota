<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}
$config = require dirname(__DIR__) . '/config/banco.php';
$dados = json_decode(stream_get_contents(STDIN), true, 512, JSON_THROW_ON_ERROR);
$nome = $config['nome'];
if (!preg_match('/^[a-z0-9_]+_teste$/', $nome)) {
    throw new RuntimeException('Use um banco exclusivo com nome terminado em _teste.');
}
$db = new PDO("mysql:host={$config['host']};port={$config['porta']};charset=utf8mb4", $config['usuario'], $config['senha'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$servidor = $db->query('SELECT VERSION() AS versao, @@version_comment AS distribuicao, @@hostname AS servidor, @@port AS porta, CURRENT_USER() AS usuario')->fetch();
if (stripos($servidor['versao'] . ' ' . $servidor['distribuicao'], 'MariaDB') !== false || stripos($servidor['distribuicao'], 'MySQL') === false || version_compare($servidor['versao'], '8.0.16', '<')) {
    throw new RuntimeException('Esta execução exige MySQL Server 8.0.16 ou superior. Nenhum banco de teste foi recriado.');
}
if ($dados['acao'] === 'preparar') {
    $db->exec("DROP DATABASE IF EXISTS `$nome`");
    $sql = file_get_contents(dirname(__DIR__) . '/database/banco.sql');
    $sql = str_replace('oficina_rota', $nome, $sql);
    $db->exec($sql);
    echo json_encode(['banco' => $nome, 'host_configurado' => $config['host']] + $servidor);
} else {
    $db->exec("USE `$nome`");
    $stmt = $db->prepare($dados['sql']);
    $stmt->execute($dados['params'] ?? []);
    echo json_encode($stmt->fetchAll(), JSON_UNESCAPED_UNICODE);
}
