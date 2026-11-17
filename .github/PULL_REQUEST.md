O Oficina Rota, de Rafael Oliveira de Souza, organiza clientes, veículos, ordens e agenda com permissões de administrador e mecânico. O sistema usa PHP/PDO, MySQL Server e HTML/CSS/JavaScript puro, com validação de sobreposição no cadastro e na edição, transação para concorrência e auditoria.

A configuração utiliza o MySQL Server local 8.0.43, serviço MySQL80, host 127.0.0.1 e porta 3306. O banco é oficina_rota. O README ensina a criar/importar pelo MySQL Workbench e conferir que o PHP conecta ao mesmo servidor e esquema. A senha é lida de config/local.php, ignorado pelo Git; o exemplo versionado não contém credenciais reais.

Validação atual: 75 verificações aprovadas no MySQL Server 8.0.43, PHP 8.2.12, Chrome via Playwright e Apache 2.4.58. Inclui os três cenários obrigatórios, collation, CHECK direto no banco, permissões via POST, filtros/paginação, concorrência e sessões independentes. As evidências atuais ficam em docs/testes, o plano em docs/plano_testes.docx e o relatório em resultados.docx.

O visual usa Arial, fundo claro, cabeçalho com links e controles comuns, com azul no Rafael e verde no Daniel. A apresentação e o CSS foram simplificados. As funcionalidades, a segurança e as configurações locais foram preservadas. As evidências antigas do MariaDB foram arquivadas e não entram na contagem de testes MySQL.
