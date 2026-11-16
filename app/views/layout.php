<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($titulo) ?> | Oficina Rota</title>
    <link rel="stylesheet" href="assets/estilo.css">
    <script src="assets/app.js" defer></script>
</head>
<body class="<?= $pagina === 'login' ? 'tela-login' : '' ?>">
<?php if ($pagina === 'login'): ?>
    <main class="login-layout">
        <section class="apresentacao">
            <a class="marca" href="index.php"><span class="simbolo">R</span><span>Oficina Rota<small>GESTÃO DE OFICINA</small></span></a>
            <div><span class="eyebrow">DA RECEPÇÃO À ENTREGA</span><h1>Sua oficina no caminho certo.</h1><p>Clientes, veículos e serviços em um só lugar. Uma rotina mais clara para toda a equipe.</p></div>
            <span class="assinatura">Rafael Oliveira de Souza · Projeto de curso técnico</span>
        </section>
        <section class="login-card"><span class="eyebrow">ACESSO DA EQUIPE</span><h2>Bom ter você por aqui.</h2><p>Entre com sua conta para continuar.</p>
            <?php if ($erro): ?><div class="mensagem erro" role="alert"><?= e($erro) ?></div><?php endif; ?>
            <form method="post" action="<?= e(url('login')) ?>" class="formulario">
                <?= csrf() ?><input type="hidden" name="acao" value="login">
                <?php campo('E-mail', 'email', $dados, 'email', 'required maxlength="150" autocomplete="username"'); ?>
                <label>Senha<input name="senha" type="password" required maxlength="72" autocomplete="current-password"></label>
                <button type="submit">Entrar no sistema <span aria-hidden="true">→</span></button>
            </form><p class="ajuda">Acesso exclusivo para administradores e mecânicos.</p>
        </section>
    </main>
<?php else: ?>
    <header class="barra">
        <a class="marca" href="index.php"><span class="simbolo">R</span><span>Oficina Rota<small>GESTÃO DE OFICINA</small></span></a>
        <nav class="menu" aria-label="Principal">
            <?php foreach (['ordens' => 'Ordens de serviço', 'agenda' => 'Agenda', 'clientes' => 'Clientes', 'veiculos' => 'Veículos'] + ($isAdmin ? ['usuarios' => 'Usuários'] : []) as $rota => $nome): ?>
                <a href="<?= e(url($rota)) ?>" <?= $pagina === $rota ? 'aria-current="page"' : '' ?>><?= e($nome) ?></a>
            <?php endforeach; ?>
        </nav>
        <?php if ($usuario): ?><div class="conta"><span><strong><?= e($usuario['nome']) ?></strong><small><?= $isAdmin ? 'Administrador' : 'Mecânico' ?></small></span><form action="<?= e(url('ordens')) ?>" method="post"><?= csrf() ?><input type="hidden" name="acao" value="logout"><button class="sair" type="submit">Sair</button></form></div><?php endif; ?>
    </header>
    <main class="conteudo">
        <div class="cabecalho"><div><span class="eyebrow">Oficina Rota / <?= $pagina === 'agenda' ? 'PROGRAMAÇÃO' : 'GESTÃO' ?></span><h1><?= e($titulo) ?></h1><p><?= $pagina === 'ordens' ? 'Acompanhe cada atendimento, do agendamento à conclusão.' : ($pagina === 'agenda' ? 'Consulte os horários e organize os atendimentos do dia.' : 'Informações organizadas para o trabalho da oficina.') ?></p></div>
        <?php if ($isAdmin && in_array($pagina, ['ordens', 'clientes', 'veiculos'], true) && !$editando): ?><a class="botao" href="<?= e(url($pagina, ['novo' => 1])) ?>">+ <?= ['ordens' => 'Nova ordem', 'clientes' => 'Novo cliente', 'veiculos' => 'Novo veículo'][$pagina] ?></a><?php endif; ?></div>
        <?php if (isset($_SESSION['aviso'])): ?><div class="mensagem sucesso" role="status"><?= e($_SESSION['aviso']) ?></div><?php unset($_SESSION['aviso']); endif; ?>
        <?php if ($erro): ?><div class="mensagem erro" role="alert"><?= e($erro) ?></div><?php endif; ?>
        <?php if (!$isAdmin && $pagina !== 'erro'): ?><p class="permissao">Você está consultando os registros vinculados aos seus atendimentos.</p><?php endif; ?>
        <?php if ($editando): require __DIR__ . '/formulario.php';
        elseif ($pagina === 'usuarios'): require __DIR__ . '/usuarios.php';
        elseif ($pagina !== 'erro'): require __DIR__ . '/listagem.php'; endif; ?>
    </main>
    <footer>Oficina Rota <span>Organização para cuidar de quem está na estrada.</span></footer>
<?php endif; ?>
</body>
</html>
