<?php
$menu = ['ordens' => 'Ordens de serviço', 'agenda' => 'Agenda', 'clientes' => 'Clientes', 'veiculos' => 'Veículos'];
if ($isAdmin) {
    $menu['usuarios'] = 'Usuários';
}
$novos = ['ordens' => 'Nova ordem', 'clientes' => 'Novo cliente', 'veiculos' => 'Novo veículo'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($titulo) ?> | Oficina Rota</title>
    <link rel="stylesheet" href="assets/estilo.css">
    <script src="assets/app.js" defer></script>
</head>
<body>
    <header class="barra">
        <a class="marca" href="index.php">Oficina Rota</a>
        <?php if ($usuario): ?>
            <nav class="menu" aria-label="Principal">
                <?php foreach ($menu as $rota => $nome): ?>
                    <a href="<?= e(url($rota)) ?>" <?= $pagina === $rota ? 'aria-current="page"' : '' ?>><?= e($nome) ?></a>
                <?php endforeach; ?>
            </nav>
            <div class="conta">
                <span><?= e($usuario['nome']) ?> (<?= $isAdmin ? 'Administrador' : 'Mecânico' ?>)</span>
                <form action="<?= e(url('ordens')) ?>" method="post">
                    <?= csrf() ?>
                    <input type="hidden" name="acao" value="logout">
                    <button type="submit">Sair</button>
                </form>
            </div>
        <?php endif; ?>
    </header>

    <main class="conteudo <?= $pagina === 'login' ? 'login' : '' ?>">
        <div class="cabecalho">
            <h1><?= e($titulo) ?></h1>
            <?php if ($isAdmin && isset($novos[$pagina]) && !$editando): ?>
                <a class="botao" href="<?= e(url($pagina, ['novo' => 1])) ?>"><?= e($novos[$pagina]) ?></a>
            <?php endif; ?>
        </div>

        <?php if (isset($_SESSION['aviso'])): ?>
            <p class="mensagem sucesso" role="status"><?= e($_SESSION['aviso']) ?></p>
            <?php unset($_SESSION['aviso']); ?>
        <?php endif; ?>
        <?php if ($erro): ?>
            <p class="mensagem erro" role="alert"><?= e($erro) ?></p>
        <?php endif; ?>

        <?php if ($pagina === 'login'): ?>
            <form method="post" action="<?= e(url('login')) ?>" class="formulario">
                <?= csrf() ?>
                <input type="hidden" name="acao" value="login">
                <?php campo('E-mail', 'email', $dados, 'email', 'required maxlength="150" autocomplete="username"'); ?>
                <label>Senha
                    <input name="senha" type="password" required maxlength="72" autocomplete="current-password">
                </label>
                <div><button type="submit">Entrar</button></div>
            </form>
        <?php else: ?>
            <?php if (!$isAdmin && $pagina !== 'erro'): ?>
                <p class="ajuda">Acesso aos registros dos seus atendimentos.</p>
            <?php endif; ?>
            <?php
            if ($editando) {
                require __DIR__ . '/formulario.php';
            } elseif ($pagina === 'usuarios') {
                require __DIR__ . '/usuarios.php';
            } elseif ($pagina !== 'erro') {
                require __DIR__ . '/listagem.php';
            }
            ?>
        <?php endif; ?>
    </main>
    <footer>Rafael Oliveira de Souza</footer>
</body>
</html>
