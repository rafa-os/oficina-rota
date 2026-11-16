<?php
declare(strict_types=1);

$pagina = texto($_GET, 'pagina', $usuario ? 'ordens' : 'login');
$permitidas = ['login', 'ordens', 'clientes', 'veiculos', 'agenda', 'usuarios'];
$erro = '';
$dados = [];
$editando = false;
$historico = [];
$lista = ['itens' => [], 'total' => 0, 'pagina' => 1];
$busca = texto($_GET, 'busca');
$data = texto($_GET, 'data', $pagina === 'agenda' ? date('Y-m-d') : '');
$isAdmin = $usuario && $usuario['perfil'] === 'administrador';

try {
    if (!in_array($pagina, $permitidas, true)) {
        throw new RegraException('Página não encontrada.', 404);
    }
    if (!$usuario && $pagina !== 'login') {
        ir('login');
    }
    if ($usuario && $pagina === 'login') {
        ir('ordens');
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!hash_equals($_SESSION['csrf'], texto($_POST, 'csrf'))) {
            throw new RegraException('Sua sessão do formulário expirou. Atualize a página e tente novamente.', 403);
        }
        $acao = texto($_POST, 'acao');
        $dados = $_POST;
        if ($acao === 'login' && $pagina === 'login') {
            if (time() < ($_SESSION['login_bloqueado'] ?? 0)) {
                throw new RegraException('Muitas tentativas. Aguarde um minuto antes de tentar novamente.', 429);
            }
            $conta = $oficina->um('SELECT * FROM usuarios WHERE email = ? AND ativo = 1', [texto($_POST, 'email')]);
            if (!$conta || !password_verify(entradaSenha($_POST), $conta['senha'])) {
                $_SESSION['tentativas'] = ($_SESSION['tentativas'] ?? 0) + 1;
                if ($_SESSION['tentativas'] >= 5) {
                    $_SESSION['login_bloqueado'] = time() + 60;
                    $_SESSION['tentativas'] = 0;
                }
                throw new RegraException('E-mail ou senha incorretos.');
            }
            session_regenerate_id(true);
            $_SESSION = ['usuario_id' => $conta['id'], 'csrf' => bin2hex(random_bytes(32))];
            ir('ordens');
        }
        if (!$usuario) {
            throw new RegraException('Faça login para continuar.', 403);
        }
        if ($acao === 'logout') {
            $_SESSION = [];
            $cookie = session_get_cookie_params();
            setcookie(session_name(), '', ['expires' => time() - 3600, 'path' => $cookie['path'], 'secure' => $cookie['secure'], 'httponly' => true, 'samesite' => 'Lax']);
            session_destroy();
            ir('login');
        }
        if ($acao === 'salvar_cadastro' && in_array($pagina, ['clientes', 'veiculos'], true)) {
            $oficina->salvarCadastro($pagina, $_POST, $usuario);
            aviso('Registro salvo com sucesso.');
            ir($pagina);
        }
        if ($acao === 'salvar_ordem' && $pagina === 'ordens') {
            $id = $oficina->salvarOrdem($_POST, $usuario);
            aviso('Ordem de serviço #' . $id . ' salva com sucesso.');
            ir('ordens', ['editar' => $id]);
        }
        if ($acao === 'cadastrar_usuario' && $pagina === 'usuarios') {
            $oficina->cadastrarUsuario($_POST, $usuario);
            aviso('Usuário cadastrado com sucesso.');
            ir('usuarios');
        }
        if ($acao === 'excluir' && in_array($pagina, ['clientes', 'veiculos', 'ordens'], true)) {
            $oficina->excluir($pagina, (int) texto($_POST, 'id'), $usuario);
            aviso('Registro excluído com sucesso.');
            ir($pagina);
        }
        throw new RegraException('Operação inválida.', 400);
    }
} catch (RegraException $e) {
    http_response_code($e->status);
    $erro = $e->getMessage();
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(422);
    $erro = $e->getCode() === '23000' ? 'Não foi possível salvar: e-mail ou placa já cadastrados, ou registro vinculado a outros dados. Exclua primeiro os vínculos permitidos.' : 'Não foi possível concluir a operação. Tente novamente.';
}

try {
    if ($pagina === 'usuarios') {
        admin($usuario);
    }
    if (in_array($pagina, ['clientes', 'veiculos', 'ordens'], true) && $usuario) {
        $id = (int) texto($_GET, 'editar');
        $novo = texto($_GET, 'novo') === '1';
        $editando = $id > 0 || $novo || ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array(texto($_POST, 'acao'), ['salvar_cadastro', 'salvar_ordem'], true));
        if ($editando) {
            if ($pagina !== 'ordens' || !$id) {
                admin($usuario);
            }
            if ($id) {
                $atual = $pagina === 'ordens' ? $oficina->ordem($id, $usuario) : $oficina->um("SELECT * FROM $pagina WHERE id = ?", [$id]);
                if (!$atual) {
                    throw new RegraException('Registro não encontrado.', 404);
                }
                if (!$dados) {
                    $dados = $atual;
                    if ($pagina === 'ordens') {
                        $dados['inicio'] = date('Y-m-d\TH:i', strtotime($atual['inicio']));
                        $dados['fim'] = date('Y-m-d\TH:i', strtotime($atual['fim']));
                    }
                }
                if ($pagina === 'ordens' && !$isAdmin) {
                    $observacoes = texto($dados, 'observacoes', $atual['observacoes']);
                    $situacao = texto($dados, 'situacao', $atual['situacao']);
                    $dados = $atual;
                    $dados['inicio'] = date('Y-m-d\TH:i', strtotime($atual['inicio']));
                    $dados['fim'] = date('Y-m-d\TH:i', strtotime($atual['fim']));
                    $dados['observacoes'] = $observacoes;
                    $dados['situacao'] = $situacao;
                }
                if ($pagina === 'ordens') {
                    $historico = $oficina->todos('SELECT o.*, u.nome FROM operacoes o JOIN usuarios u ON u.id = o.usuario_id WHERE ordem_numero = ? ORDER BY o.id DESC', [$id]);
                }
            }
        }
    }
    if ($usuario && in_array($pagina, ['ordens', 'clientes', 'veiculos', 'agenda'], true)) {
        $lista = $oficina->listar($pagina === 'agenda' ? 'ordens' : $pagina, $usuario, $busca, $data, max(1, (int) texto($_GET, 'p', '1')), $pagina === 'agenda');
    }
} catch (RegraException $e) {
    http_response_code($e->status);
    $erro = $e->getMessage();
    $editando = false;
    if ($e->status === 403 || $e->status === 404) {
        $pagina = 'erro';
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    $erro = 'Não foi possível consultar os registros.';
    $pagina = 'erro';
}
$titulos = ['ordens' => 'Ordens de serviço', 'clientes' => 'Clientes', 'veiculos' => 'Veículos', 'agenda' => 'Agenda dos mecânicos', 'usuarios' => 'Usuários', 'erro' => 'Acesso indisponível', 'login' => 'Bem-vindo'];
$titulo = $titulos[$pagina] ?? 'Página não encontrada';
if (!in_array($pagina, [...$permitidas, 'erro'], true)) {
    $pagina = 'erro';
}
require dirname(__DIR__) . '/views/layout.php';
