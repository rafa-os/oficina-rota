<?php
declare(strict_types=1);

$config = [
    'host' => '127.0.0.1',
    'porta' => '3306',
    'nome' => 'oficina_rota',
    'usuario' => 'root',
    'senha' => '',
];
$arquivoLocal = __DIR__ . '/local.php';
if (is_file($arquivoLocal)) {
    $config = array_replace($config, require $arquivoLocal);
}
foreach (['host' => 'DB_HOST', 'porta' => 'DB_PORT', 'nome' => 'DB_NAME', 'usuario' => 'DB_USER', 'senha' => 'DB_PASS'] as $chave => $variavel) {
    $valor = getenv($variavel);
    if ($valor !== false) {
        $config[$chave] = $valor;
    }
}
return $config;
