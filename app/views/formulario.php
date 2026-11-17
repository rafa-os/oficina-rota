<?php
$titulosForm = ['clientes' => 'cliente', 'veiculos' => 'veículo', 'ordens' => 'ordem'];
$tituloForm = !empty($dados['id']) ? 'Editar ' . $titulosForm[$pagina] . ' #' . texto($dados, 'id') : ['clientes' => 'Novo cliente', 'veiculos' => 'Novo veículo', 'ordens' => 'Nova ordem'][$pagina];
$parametros = !empty($dados['id']) ? ['editar' => $dados['id']] : ['novo' => 1];
?>
<section class="secao">
    <div class="secao-titulo">
        <h2><?= e($tituloForm) ?></h2>
        <a href="<?= e(url($pagina)) ?>">Voltar</a>
    </div>
    <form method="post" action="<?= e(url($pagina, $parametros)) ?>" class="formulario">
        <?= csrf() ?>
        <input type="hidden" name="id" value="<?= e($dados['id'] ?? 0) ?>">
        <input type="hidden" name="acao" value="<?= $pagina === 'ordens' ? 'salvar_ordem' : 'salvar_cadastro' ?>">

        <div class="campos">
            <?php if ($pagina === 'clientes'): ?>
                <?php
                campo('Nome', 'nome', $dados, 'text', 'required minlength="3" maxlength="100"');
                campo('Telefone com DDD', 'telefone', $dados, 'tel', 'required minlength="8" maxlength="20"');
                campo('E-mail (opcional)', 'email', $dados, 'email', 'maxlength="150"');
                ?>
            <?php elseif ($pagina === 'veiculos'): ?>
                <?php
                $clientes = $oficina->todos('SELECT id, nome FROM clientes ORDER BY nome');
                selecao('Proprietário', 'cliente_id', array_column($clientes, 'nome', 'id'), $dados['cliente_id'] ?? '');
                campo('Placa', 'placa', $dados, 'text', 'required maxlength="8" placeholder="ABC1D23"');
                campo('Marca', 'marca', $dados, 'text', 'required minlength="2" maxlength="50"');
                campo('Modelo', 'modelo', $dados, 'text', 'required minlength="2" maxlength="80"');
                campo('Ano', 'ano', $dados, 'number', 'required min="1900" max="' . (date('Y') + 1) . '"');
                ?>
            <?php elseif ($pagina === 'ordens'): ?>
                <?php if ($isAdmin): ?>
                    <?php
                    $veiculos = $oficina->todos("SELECT v.id, CONCAT(v.placa, ' - ', v.modelo, ' - ', c.nome) AS nome FROM veiculos v JOIN clientes c ON c.id = v.cliente_id ORDER BY c.nome, v.placa");
                    $mecanicos = $oficina->todos("SELECT id, nome FROM usuarios WHERE perfil = 'mecanico' AND ativo = 1 ORDER BY nome");
                    selecao('Veículo e cliente', 'veiculo_id', array_column($veiculos, 'nome', 'id'), $dados['veiculo_id'] ?? '');
                    selecao('Mecânico', 'mecanico_id', array_column($mecanicos, 'nome', 'id'), $dados['mecanico_id'] ?? '');
                    campo('Início', 'inicio', $dados, 'datetime-local', 'required');
                    campo('Término', 'fim', $dados, 'datetime-local', 'required');
                    ?>
                <?php else: ?>
                    <div class="resumo">
                        <p><?= e(dataBr($dados['inicio'])) ?> até <?= e(dataBr($dados['fim'])) ?></p>
                        <p><?= e($dados['descricao']) ?></p>
                    </div>
                <?php endif; ?>
                <?php
                $opcoesSituacao = situacoes();
                if (!$isAdmin) {
                    unset($opcoesSituacao['cancelada']);
                }
                selecao('Situação', 'situacao', $opcoesSituacao, $dados['situacao'] ?? 'agendada');
                ?>
            <?php endif; ?>
        </div>

        <?php if ($pagina === 'ordens'): ?>
            <?php if ($isAdmin): ?>
                <label>Descrição do serviço
                    <textarea name="descricao" required minlength="5" maxlength="1000" rows="3"><?= e($dados['descricao'] ?? '') ?></textarea>
                </label>
            <?php endif; ?>
            <label>Observações
                <textarea name="observacoes" maxlength="2000" rows="3"><?= e($dados['observacoes'] ?? '') ?></textarea>
            </label>
            <p class="ajuda">O mecânico deve estar livre no intervalo. Ordens canceladas liberam o horário.</p>
        <?php endif; ?>

        <div class="acoes-form">
            <button type="submit">Salvar</button>
            <a href="<?= e(url($pagina)) ?>">Cancelar</a>
        </div>
    </form>
</section>

<?php if ($historico): ?>
    <section class="historico">
        <h2>Histórico</h2>
        <ul>
            <?php foreach ($historico as $evento): ?>
                <li><?= e(['cadastro' => 'Cadastro', 'atualizacao' => 'Atualização', 'exclusao' => 'Exclusão'][$evento['acao']]) ?> - <?= e($evento['nome']) ?> - <?= e(dataBr($evento['criado_em'])) ?></li>
            <?php endforeach; ?>
        </ul>
    </section>
<?php endif; ?>
