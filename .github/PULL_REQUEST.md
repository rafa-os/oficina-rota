A oficina precisa controlar permissões e impedir que um mecânico receba atendimentos sobrepostos. Esta implementação entrega o Oficina Rota, de Rafael Oliveira de Souza, com cadastros de clientes, veículos e ordens, login com sessões PHP, perfis de administrador e mecânico, filtros e paginação no servidor.

A criação e a edição de ordens verificam o intervalo completo em uma transação InnoDB. Por exemplo, uma reserva das 09h às 10h impede outro serviço às 09h30 para o mesmo mecânico. Horários contíguos são aceitos. A auditoria registra usuário, data/hora e os valores anteriores/posteriores.

Validação executada: 68 verificações aprovadas em PHP 8.2.12, MariaDB 10.4.32, Chrome via Playwright e Apache 2.4.58. Inclui os três cenários exigidos, conferência dos registros no banco, permissões via POST, paginação, layout a 390 px e duas conexões concorrentes. Evidências reais em docs/testes, plano em docs/plano_testes.docx e relatório em resultados.docx.

Instalação no XAMPP e contas de demonstração estão no README. Não foi fornecido repositório/código anterior; o projeto foi criado do zero com base nos anexos. MySQL 8 não foi executado neste ambiente.
