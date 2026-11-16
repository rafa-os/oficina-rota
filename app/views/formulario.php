<section class="painel">
    <div class="secao-titulo"><h2><?= !empty($dados['id']) ? 'Editar registro #' . e($dados['id']) : 'Novo registro' ?></h2><a href="<?= e(url($pagina)) ?>">← Voltar à lista</a></div>
    <form method="post" action="<?= e(url($pagina, !empty($dados['id']) ? ['editar' => $dados['id']] : ['novo' => 1])) ?>" class="formulario">
        <?= csrf() ?><input type="hidden" name="id" value="<?= e($dados['id'] ?? 0) ?>">
        <input type="hidden" name="acao" value="<?= $pagina === 'ordens' ? 'salvar_ordem' : 'salvar_cadastro' ?>">
        <div class="grade-form">
        <?php if ($pagina === 'clientes'):
            campo('Nome completo', 'nome', $dados, 'text', 'required minlength="3" maxlength="100"');
            campo('Telefone com DDD', 'telefone', $dados, 'tel', 'required minlength="8" maxlength="20"');
            campo('E-mail (opcional)', 'email', $dados, 'email', 'maxlength="150"');
        elseif ($pagina === 'veiculos'):
            selecao('Proprietário', 'cliente_id', array_column($oficina->todos('SELECT id, nome FROM clientes ORDER BY nome'), 'nome', 'id'), $dados['cliente_id'] ?? '');
            campo('Placa', 'placa', $dados, 'text', 'required maxlength="8" placeholder="ABC1D23"');
            campo('Marca', 'marca', $dados, 'text', 'required minlength="2" maxlength="50"');
            campo('Modelo', 'modelo', $dados, 'text', 'required minlength="2" maxlength="80"');
            campo('Ano', 'ano', $dados, 'number', 'required min="1900" max="' . (date('Y') + 1) . '"');
        elseif ($pagina === 'ordens'):
            if ($isAdmin):
                $veiculos = $oficina->todos("SELECT v.id, CONCAT(v.placa, ' · ', v.modelo, ' · ', c.nome) AS nome FROM veiculos v JOIN clientes c ON c.id = v.cliente_id ORDER BY c.nome, v.placa");
                selecao('Veículo e cliente', 'veiculo_id', array_column($veiculos, 'nome', 'id'), $dados['veiculo_id'] ?? '');
                selecao('Mecânico responsável', 'mecanico_id', array_column($oficina->todos("SELECT id, nome FROM usuarios WHERE perfil = 'mecanico' AND ativo = 1 ORDER BY nome"), 'nome', 'id'), $dados['mecanico_id'] ?? '');
                campo('Início do atendimento', 'inicio', $dados, 'datetime-local', 'required');
                campo('Término do atendimento', 'fim', $dados, 'datetime-local', 'required');
            else: ?>
                <div class="resumo-ordem"><strong>Atendimento #<?= e($dados['id']) ?></strong><p><?= e(dataBr($dados['inicio'])) ?> até <?= e(dataBr($dados['fim'])) ?></p><p><?= e($dados['descricao']) ?></p></div>
            <?php endif;
            selecao('Situação', 'situacao', $isAdmin ? situacoes() : array_diff_key(situacoes(), ['cancelada' => '']), $dados['situacao'] ?? 'agendada');
        endif; ?>
        </div>
        <?php if ($pagina === 'ordens'): ?>
            <?php if ($isAdmin): ?><label>Descrição do serviço<textarea name="descricao" required minlength="5" maxlength="1000" rows="3"><?= e($dados['descricao'] ?? '') ?></textarea></label><?php endif; ?>
            <label>Observações da execução<textarea name="observacoes" maxlength="2000" rows="3"><?= e($dados['observacoes'] ?? '') ?></textarea></label>
            <p class="ajuda">Os horários não podem se sobrepor para o mesmo mecânico. Atendimentos cancelados liberam a agenda.</p>
        <?php endif; ?>
        <div class="acoes-form"><button type="submit">Salvar <?= $pagina === 'ordens' ? 'ordem de serviço' : 'registro' ?></button><a class="botao secundario" href="<?= e(url($pagina)) ?>">Cancelar</a></div>
    </form>
</section>
<?php if ($historico): ?><section class="painel historico"><h2>Histórico desta ordem</h2><ul><?php foreach ($historico as $evento): ?><li><strong><?= e(['cadastro' => 'Cadastro', 'atualizacao' => 'Atualização', 'exclusao' => 'Exclusão'][$evento['acao']]) ?></strong> · <?= e($evento['nome']) ?> · <?= e(dataBr($evento['criado_em'])) ?></li><?php endforeach; ?></ul></section><?php endif; ?>
