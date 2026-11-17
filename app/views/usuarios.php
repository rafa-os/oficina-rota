<section class="secao">
    <h2>Novo usuário</h2>
    <form method="post" action="<?= e(url('usuarios')) ?>" class="formulario">
        <?= csrf() ?>
        <input type="hidden" name="acao" value="cadastrar_usuario">
        <div class="campos">
            <?php
            campo('Nome', 'nome', $dados, 'text', 'required minlength="3" maxlength="100"');
            campo('E-mail', 'email', $dados, 'email', 'required maxlength="150"');
            ?>
            <label>Senha inicial
                <input type="password" name="senha" required minlength="8" maxlength="72" autocomplete="new-password">
            </label>
            <?php selecao('Perfil', 'perfil', ['administrador' => 'Administrador', 'mecanico' => 'Mecânico'], $dados['perfil'] ?? 'mecanico'); ?>
        </div>
        <div><button type="submit">Salvar</button></div>
    </form>
</section>
