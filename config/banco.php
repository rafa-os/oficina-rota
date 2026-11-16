<?php
return [
    'host' => getenv('DB_HOST') ?: '127.0.0.1',
    'porta' => getenv('DB_PORT') ?: '3306',
    'nome' => getenv('DB_NAME') ?: 'oficina_rota',
    'usuario' => getenv('DB_USER') ?: 'root',
    'senha' => getenv('DB_PASS') ?: '',
];
