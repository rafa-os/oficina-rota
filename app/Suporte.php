<?php
declare(strict_types=1);

class RegraException extends RuntimeException
{
    public function __construct(string $message, public int $status = 422)
    {
        parent::__construct($message);
    }
}

function texto(array $dados, string $chave, string $padrao = ''): string
{
    return isset($dados[$chave]) && is_scalar($dados[$chave]) ? trim((string) $dados[$chave]) : $padrao;
}

function entradaSenha(array $dados): string
{
    return isset($dados['senha']) && is_string($dados['senha']) ? $dados['senha'] : '';
}

function e(mixed $valor): string
{
    return htmlspecialchars(is_scalar($valor) ? (string) $valor : '', ENT_QUOTES, 'UTF-8');
}

function url(string $pagina, array $params = []): string
{
    return 'index.php?' . http_build_query(['pagina' => $pagina] + $params);
}

function ir(string $pagina, array $params = []): never
{
    header('Location: ' . url($pagina, $params));
    exit;
}

function aviso(string $mensagem): void
{
    $_SESSION['aviso'] = $mensagem;
}

function csrf(): string
{
    return '<input type="hidden" name="csrf" value="' . e($_SESSION['csrf']) . '">';
}

function admin(?array $usuario): void
{
    if (!$usuario || $usuario['perfil'] !== 'administrador') {
        throw new RegraException('Você não tem permissão para realizar esta operação.', 403);
    }
}

function campo(string $rotulo, string $nome, array $dados, string $tipo = 'text', string $atributos = ''): void
{
    echo '<label>' . e($rotulo) . '<input type="' . e($tipo) . '" name="' . e($nome) . '" value="' . e($dados[$nome] ?? '') . '" ' . $atributos . '></label>';
}

function selecao(string $rotulo, string $nome, array $opcoes, mixed $atual): void
{
    $atual = is_scalar($atual) ? $atual : '';
    echo '<label>' . e($rotulo) . '<select name="' . e($nome) . '" required><option value="">Selecione</option>';
    foreach ($opcoes as $id => $nomeOpcao) {
        echo '<option value="' . e($id) . '" ' . ((string) $id === (string) $atual ? 'selected' : '') . '>' . e($nomeOpcao) . '</option>';
    }
    echo '</select></label>';
}

function dataBr(string $data): string
{
    return date('d/m/Y H:i', strtotime($data));
}

function situacoes(): array
{
    return ['agendada' => 'Agendada', 'em_andamento' => 'Em andamento', 'concluida' => 'Concluída', 'cancelada' => 'Cancelada'];
}

function paginar(string $pagina, int $total, int $paginaAtual, array $filtros): void
{
    $paginas = max(1, (int) ceil($total / 5));
    echo '<nav class="paginacao" aria-label="Paginação"><span>' . $total . ' registro(s) · Página ' . $paginaAtual . ' de ' . $paginas . '</span>';
    if ($paginaAtual > 1) {
        echo '<a href="' . e(url($pagina, $filtros + ['p' => $paginaAtual - 1])) . '">Anterior</a>';
    }
    if ($paginaAtual < $paginas) {
        echo '<a href="' . e(url($pagina, $filtros + ['p' => $paginaAtual + 1])) . '">Próxima</a>';
    }
    echo '</nav>';
}
