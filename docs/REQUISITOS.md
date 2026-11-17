# Requisitos e decisões

Fontes lidas: DESENVOLVIMENTO DE SISTEMA.pdf (Prof.ª Noemi) e Texto colado(20261116-223033).txt, fornecidos em 16/11/2026. Os dois contêm os mesmos requisitos. Não foi fornecido repositório ou código existente; esta implementação parte do zero.

| Requisito | Implementação |
| --- | --- |
| Autenticação | Sessões PHP distintas por sistema, hash de senha e logout via POST |
| Administrador | Cadastra usuários e consulta, cadastra, altera e exclui clientes, veículos e ordens |
| Mecânico | Consulta somente ordens atribuídas a ele e respectivos clientes/veículos; registra observações e situação nessas ordens |
| Restrição do mecânico | Não cria clientes, veículos ou ordens, não muda horários, descrição, responsável ou proprietário e não exclui registros |
| Consulta | Nome do cliente e data inicial do atendimento; clientes/veículos também possuem busca e paginação |
| Paginação | Cinco registros por página com COUNT, LIMIT e OFFSET no servidor |
| Agenda | Lista atendimentos que intersectam o dia selecionado, inclusive os que começaram no dia anterior |
| Sobreposição | novo início < término existente E novo término > início existente; horários contíguos são permitidos |
| Atualização | Exclui a própria ordem da pesquisa de conflitos e valida novamente, inclusive na troca de mecânico |
| Concorrência | Transação InnoDB bloqueia a linha única de controle_agenda antes de consultar e gravar; todas as alterações de ordens usam esse bloqueio |
| Situações | Agendada, em andamento, concluída e cancelada; somente cancelada libera o intervalo |
| Integridade | FK impede excluir cliente com veículo ou veículo com ordem; proprietário de veículo com ordens não pode mudar |
| Auditoria | Cadastro, atualização e exclusão de ordens gravam ator, data/hora e estados anterior/posterior na mesma transação |
| Exclusão de ordem | Permitida ao administrador; histórico permanece em operacoes, inclusive após excluir a ordem |

As permissões concretizam a expressão “conforme as permissões definidas” dos anexos. O cadastro de usuários é administrativo; não há autorregistro com escolha de perfil. Os dados de demonstração são fictícios. Horários usam America/Sao_Paulo. As evidências executadas e suas limitações estão em resultados.docx.

A arquitetura separa conexão/configuração, controlador HTTP, repositório/regras de negócio e apresentação. Os dois projetos não compartilham arquivos em execução nem tabelas. Não há dependências de frontend nem de PHP.

## Ambiente MySQL Server

A conexão atual usa MySQL80 em 127.0.0.1:3306. O esquema foi ajustado para MySQL 8, com utf8mb4_0900_ai_ci e CHECK de intervalo válido. O Workbench e o PHP devem selecionar o mesmo banco normal; a automação usa um banco de teste separado no mesmo servidor. As credenciais locais ficam em config/local.php, fora do Git. As regras de negócio e as configurações de banco foram preservadas.

## Apresentação simples

Cabeçalho, menu de links, tabelas e formulários comuns, fonte Arial e fundo claro. Azul para Oficina Rota e verde para Ponto Motor. Os botões usam textos curtos. O CSS foi reduzido e a marcação das telas foi organizada em blocos legíveis, mantendo os arquivos existentes. A paginação recebe a rota por parâmetro. Permissões, validações, sessões, PDO, agenda e auditoria permanecem ativos.
