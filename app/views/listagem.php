<?php $ordens = in_array($pagina, ['ordens', 'agenda'], true); ?>
<section class="painel">
    <form method="get" action="index.php" class="filtros">
        <input type="hidden" name="pagina" value="<?= e($pagina) ?>">
        <label><?= $ordens ? 'Nome do cliente' : 'Buscar registro' ?><input name="busca" type="search" maxlength="100" value="<?= e($busca) ?>" placeholder="<?= $ordens ? 'Digite o nome do cliente' : 'Digite para consultar' ?>"></label>
        <?php if ($ordens): ?><label>Data do atendimento<input type="date" name="data" value="<?= e($data) ?>" <?= $pagina === 'agenda' ? 'required' : '' ?>></label><?php endif; ?>
        <button type="submit">Filtrar</button><a class="limpar" href="<?= e(url($pagina)) ?>">Limpar</a>
    </form>
    <div class="linha-secao"><h2><?= $pagina === 'agenda' ? 'Programação · ' . e(date('d/m/Y', strtotime($data ?: date('Y-m-d')))) : 'Registros cadastrados' ?></h2><span><?= $lista['total'] ?> encontrado(s)</span></div>
    <div class="tabela-wrap"><table>
        <thead><tr><?php if ($ordens): ?><th>Ordem / Cliente</th><th>Veículo / Serviço</th><th>Mecânico</th><th>Agendamento</th><th>Situação</th><th>Ações</th>
        <?php elseif ($pagina === 'clientes'): ?><th>Nome</th><th>Telefone</th><th>E-mail</th><?php if ($isAdmin): ?><th>Ações</th><?php endif; ?>
        <?php else: ?><th>Placa</th><th>Veículo</th><th>Ano</th><th>Proprietário</th><?php if ($isAdmin): ?><th>Ações</th><?php endif; ?><?php endif; ?></tr></thead>
        <tbody>
        <?php foreach ($lista['itens'] as $item): ?><tr>
            <?php if ($ordens): ?><td><span class="numero">#<?= e($item['id']) ?></span><strong><?= e($item['cliente']) ?></strong></td><td><strong><?= e($item['modelo']) ?> · <?= e($item['placa']) ?></strong><span class="descricao"><?= e($item['descricao']) ?></span></td><td><?= e($item['mecanico']) ?></td><td class="horario"><strong><?= e(dataBr($item['inicio'])) ?></strong><span>até <?= e(dataBr($item['fim'])) ?></span></td><td><span class="status <?= e($item['situacao']) ?>"><?= e(situacoes()[$item['situacao']]) ?></span></td>
            <?php elseif ($pagina === 'clientes'): ?><td><strong><?= e($item['nome']) ?></strong></td><td><?= e($item['telefone']) ?></td><td><?= e($item['email'] ?: 'Não informado') ?></td>
            <?php else: ?><td><strong><?= e($item['placa']) ?></strong></td><td><?= e($item['marca']) ?> <?= e($item['modelo']) ?></td><td><?= e($item['ano']) ?></td><td><?= e($item['cliente']) ?></td><?php endif; ?>
            <?php if ($isAdmin || $ordens): ?><td><div class="acoes"><a href="<?= e(url($ordens ? 'ordens' : $pagina, ['editar' => $item['id']])) ?>"><?= $isAdmin ? 'Editar' : 'Atualizar' ?></a>
            <?php if ($isAdmin): ?><form method="post" action="<?= e(url($ordens ? 'ordens' : $pagina)) ?>" data-confirmar="Excluir este registro? Esta ação não pode ser desfeita."><?= csrf() ?><input type="hidden" name="acao" value="excluir"><input type="hidden" name="id" value="<?= e($item['id']) ?>"><button type="submit" class="excluir">Excluir</button></form><?php endif; ?></div></td><?php endif; ?>
        </tr><?php endforeach; ?>
        <?php if (!$lista['itens']): ?><tr><td colspan="6" class="vazio">Nenhum registro encontrado para esta consulta.</td></tr><?php endif; ?>
        </tbody>
    </table></div>
    <?php paginar($lista['total'], $lista['pagina'], ['busca' => $busca, 'data' => $data]); ?>
</section>
