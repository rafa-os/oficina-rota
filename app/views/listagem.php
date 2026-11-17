<?php
$ordens = in_array($pagina, ['ordens', 'agenda'], true);
$destino = $ordens ? 'ordens' : $pagina;
$colunas = $ordens ? 6 : ($pagina === 'clientes' ? 3 : 4) + (int) $isAdmin;
?>
<section class="secao">
    <form method="get" action="index.php" class="filtros">
        <input type="hidden" name="pagina" value="<?= e($pagina) ?>">
        <label><?= $ordens ? 'Nome do cliente' : 'Buscar' ?>
            <input name="busca" type="search" maxlength="100" value="<?= e($busca) ?>">
        </label>
        <?php if ($ordens): ?>
            <label>Data do atendimento
                <input type="date" name="data" value="<?= e($data) ?>" <?= $pagina === 'agenda' ? 'required' : '' ?>>
            </label>
        <?php endif; ?>
        <button type="submit">Filtrar</button>
        <a href="<?= e(url($pagina)) ?>">Limpar</a>
    </form>

    <div class="tabela-wrap">
        <table>
            <thead>
                <tr>
                    <?php if ($ordens): ?>
                        <th>Ordem / Cliente</th>
                        <th>Veículo / Serviço</th>
                        <th>Mecânico</th>
                        <th>Agendamento</th>
                        <th>Situação</th>
                        <th>Ações</th>
                    <?php elseif ($pagina === 'clientes'): ?>
                        <th>Nome</th>
                        <th>Telefone</th>
                        <th>E-mail</th>
                        <?php if ($isAdmin): ?><th>Ações</th><?php endif; ?>
                    <?php else: ?>
                        <th>Placa</th>
                        <th>Veículo</th>
                        <th>Ano</th>
                        <th>Proprietário</th>
                        <?php if ($isAdmin): ?><th>Ações</th><?php endif; ?>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($lista['itens'] as $item): ?>
                    <tr>
                        <?php if ($ordens): ?>
                            <td><span class="numero">#<?= e($item['id']) ?></span><?= e($item['cliente']) ?></td>
                            <td><?= e($item['modelo']) ?> - <?= e($item['placa']) ?><span class="descricao"><?= e($item['descricao']) ?></span></td>
                            <td><?= e($item['mecanico']) ?></td>
                            <td class="horario"><?= e(dataBr($item['inicio'])) ?><br>até <?= e(dataBr($item['fim'])) ?></td>
                            <td><?= e(situacoes()[$item['situacao']]) ?></td>
                        <?php elseif ($pagina === 'clientes'): ?>
                            <td><?= e($item['nome']) ?></td>
                            <td><?= e($item['telefone']) ?></td>
                            <td><?= e($item['email'] ?: 'Não informado') ?></td>
                        <?php else: ?>
                            <td><?= e($item['placa']) ?></td>
                            <td><?= e($item['marca']) ?> <?= e($item['modelo']) ?></td>
                            <td><?= e($item['ano']) ?></td>
                            <td><?= e($item['cliente']) ?></td>
                        <?php endif; ?>
                        <?php if ($isAdmin || $ordens): ?>
                            <td>
                                <div class="acoes">
                                    <a href="<?= e(url($destino, ['editar' => $item['id']])) ?>"><?= $isAdmin ? 'Editar' : 'Atualizar' ?></a>
                                    <?php if ($isAdmin): ?>
                                        <form method="post" action="<?= e(url($destino)) ?>" data-confirmar="Excluir este registro?">
                                            <?= csrf() ?>
                                            <input type="hidden" name="acao" value="excluir">
                                            <input type="hidden" name="id" value="<?= e($item['id']) ?>">
                                            <button type="submit" class="excluir">Excluir</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$lista['itens']): ?>
                    <tr><td colspan="<?= $colunas ?>">Nenhum registro encontrado para esta consulta.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php paginar($pagina, $lista['total'], $lista['pagina'], ['busca' => $busca, 'data' => $data]); ?>
</section>
