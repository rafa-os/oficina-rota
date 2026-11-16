<section class="painel"><h2>Cadastrar usuário da equipe</h2><p class="ajuda">Somente administradores podem cadastrar contas e definir perfis.</p>
    <form method="post" action="<?= e(url('usuarios')) ?>" class="formulario">
        <?= csrf() ?><input type="hidden" name="acao" value="cadastrar_usuario">
        <div class="grade-form">
        <?php campo('Nome completo', 'nome', $dados, 'text', 'required minlength="3" maxlength="100"'); campo('E-mail', 'email', $dados, 'email', 'required maxlength="150"'); ?>
        <label>Senha inicial<input type="password" name="senha" required minlength="8" maxlength="72" autocomplete="new-password"></label>
        <?php selecao('Perfil de acesso', 'perfil', ['administrador' => 'Administrador', 'mecanico' => 'Mecânico'], $dados['perfil'] ?? 'mecanico'); ?>
        </div><div class="acoes-form"><button type="submit">Cadastrar usuário</button></div>
    </form>
</section>
