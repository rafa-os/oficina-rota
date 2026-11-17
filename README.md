# Oficina Rota

Sistema de gestão de oficina desenvolvido por **Rafael Oliveira de Souza** para a atividade da Prof.ª Noemi. HTML, CSS, JavaScript puro, PHP e PDO com MySQL Server. O visual é azul, com navegação superior. O projeto funciona sozinho e usa o banco `oficina_rota`.

## MySQL Server e MySQL Workbench

Ambiente conferido neste computador: **MySQL Server 8.0.43**, serviço Windows **MySQL80**, **127.0.0.1:3306**, e **MySQL Workbench 8.0.43**. O banco deste projeto é **`oficina_rota`**. Os bancos de demonstração já foram criados nessa instância. As instruções abaixo também servem para uma instalação nova.

### Criar/importar pelo Workbench

1. Confira se o serviço **MySQL80** está em execução em `services.msc`.
2. Abra o MySQL Workbench. Use uma conexão local com método **Standard (TCP/IP)**, hostname **127.0.0.1**, porta **3306**, usuário **root** (ou a conta local autorizada) e sua senha do MySQL. O cadastro `Local instance MySQL80` foi identificado; há também conexões em 127.0.0.1:3306. Ao criar uma conexão nova, deixe Default Schema vazio até a primeira importação.
3. Clique em **Test Connection** e abra a conexão.
4. Use **File > Open SQL Script** (`Ctrl+Shift+O`) para abrir `database/banco.sql` deste projeto.
5. Sem selecionar apenas um trecho, use **Query > Execute (All or Selection)** (`Ctrl+Shift+Enter`). O script cria o banco `oficina_rota`, seleciona-o com USE e cria tabelas e dados fictícios.
6. Confira o painel **Action Output** e atualize **Schemas**. Selecione `oficina_rota` como **Set as Default Schema**.
7. Abra e execute `database/verificar_conexao.sql`. O resultado deve mostrar banco `oficina_rota`, porta 3306 e MySQL 8.0.43 neste computador. Na carga inicial, existem 3 usuários, 6 clientes, 6 veículos e 6 ordens.

O SQL é para uma primeira carga. Ele não apaga um banco existente e as tabelas não devem ser importadas novamente sobre a carga já criada. O projeto usa MySQL 8.0.16 ou superior, InnoDB, utf8mb4_0900_ai_ci e CHECK de término posterior ao início.

### Conectar o PHP ao mesmo banco

1. Use PHP 8.1+ com `pdo_mysql`. No XAMPP, utilize o PHP de `C:\xampp\php`.
2. Copie `oficina-rafael` para `C:\xampp\htdocs\oficina-rafael` e inicie **Apache** no XAMPP. O banco utilizado é o serviço **MySQL80** do MySQL Server.
3. Se `config/local.php` ainda não existir, copie `config/local.exemplo.php` para esse nome. Preencha a mesma senha e o mesmo usuário usados no Workbench. Mantenha host `127.0.0.1`, porta `3306` e banco `oficina_rota`. Neste computador, a configuração local já foi preenchida e a conexão foi testada.
4. `config/banco.php` carrega os padrões, depois `config/local.php`, e por último as variáveis DB_HOST, DB_PORT, DB_NAME, DB_USER e DB_PASS, quando definidas. Essas variáveis têm prioridade sobre o arquivo local.
5. Abra **http://localhost/oficina-rafael/public/**. A raiz também redireciona para public/.

`config/local.php` está no `.gitignore`; a senha não é versionada. O exemplo versionado contém somente um texto para preenchimento. O usuário e a senha do MySQL são as credenciais de conexão ao banco; as contas da tabela abaixo são de acesso à aplicação.

Para conferir a identidade da conexão PHP, execute a partir desta pasta:

```powershell
& 'C:/xampp/php/php.exe' tests/conexao.php
```

Compare servidor, porta, versão e banco com o resultado de `database/verificar_conexao.sql` no Workbench. O comando não mostra a senha e não altera dados.

O Apache deve aceitar `.htaccess` (AllowOverride All, padrão do XAMPP). As pastas internas ficam bloqueadas para acesso HTTP. Em hospedagem configurável, use public/ como DocumentRoot. Para executar sem Apache, com o MySQL80 já ativo:

```powershell
& 'C:/xampp/php/php.exe' -S 127.0.0.1:8001 -t public
```

Abra `http://127.0.0.1:8001/`.

Se houver erro 1045, confira usuário/senha do MySQL em config/local.php; se houver erro 2002, confira o serviço MySQL80 e o endpoint; se houver erro 1049, importe o banco indicado. Se o Windows impedir iniciar MySQL80, use Serviços do Windows com uma conta administradora.

Referências oficiais: [execução de scripts no Workbench](https://dev.mysql.com/doc/workbench/en/wb-sql-editor-main-menu.html), [collation do MySQL 8](https://dev.mysql.com/doc/refman/8.0/en/charset-server.html) e [CHECK a partir do MySQL 8.0.16](https://dev.mysql.com/doc/refman/8.0/en/create-table-check-constraints.html).

## Credenciais de demonstração

| Perfil | E-mail | Senha |
| --- | --- | --- |
| Administrador | admin@rota.test | Oficina@123 |
| Mecânico | mecanico@rota.test | Oficina@123 |
| Segundo mecânico | mecanico2@rota.test | Oficina@123 |

As senhas do SQL estão armazenadas com hash. Os e-mails `.test` são fictícios. A carga inicial contém seis clientes, seis veículos e seis ordens. Consulte a agenda de **17/11/2026** para ver quatro atendimentos; a data do dia é selecionada por padrão.

## Uso e permissões

O administrador cadastra usuários, clientes, veículos e ordens, altera informações e exclui registros. Ao criar uma ordem, seleciona veículo/proprietário, mecânico, início, término, descrição e situação. Mecânicos consultam seus atendimentos e os respectivos clientes/veículos, registram observações e atualizam a situação das próprias ordens. Eles não excluem registros nem alteram agendamento, responsável, veículo ou descrição. A verificação ocorre no backend.

Ordens possuem filtros por nome do cliente e data do início, com cinco registros por página. Clientes e veículos também têm busca e paginação. A agenda considera qualquer intervalo que intersecte o dia consultado. Horários usam America/Sao_Paulo.

Sobreposições do mesmo mecânico são bloqueadas no cadastro e na edição, inclusive quando se troca o responsável. Um atendimento que termina às 10h permite o próximo às 10h. Ordens canceladas liberam o intervalo; ordens concluídas preservam a reserva histórica. A própria ordem não conflita consigo mesma. Uma transação com bloqueio da linha `controle_agenda` protege a validação contra gravações concorrentes.

O histórico da ordem mostra ator e data/hora. A tabela `operacoes` também guarda os dados anteriores e posteriores, inclusive após uma exclusão. Clientes com veículos e veículos com ordens não podem ser excluídos; um veículo com ordens mantém o proprietário para preservar o histórico. Formulários usam CSRF, escape HTML, validações e consultas preparadas. Login regenera a sessão e logout é enviado por POST.

## Organização

| Caminho | Responsabilidade |
| --- | --- |
| public/ | Entrada HTTP, CSS e JavaScript |
| config/banco.php | Padrões e variáveis de ambiente |
| config/local.php | Credenciais locais, ignoradas pelo Git |
| app/bootstrap.php | Inicialização, sessão e conexão PDO |
| app/controllers/ | Fluxo das requisições e autorização |
| app/models/Oficina.php | Consultas, validações, agenda e auditoria |
| app/views/ | Formulários, listas e layout |
| database/banco.sql | Esquema e dados fictícios |
| docs/REQUISITOS.md | Mapeamento dos requisitos e decisões |
| tests/ | Automação opcional de verificação |

Não há bibliotecas, frameworks ou gerenciadores de pacotes necessários para rodar o sistema.

## Testes e evidências no MySQL

Foram executadas **71 verificações aprovadas em MySQL Server 8.0.43**: 57 na automação principal e 14 pelo Apache. A execução principal usou `oficina_rota_teste` no mesmo servidor 127.0.0.1:3306; a conferência Apache usou o banco normal `oficina_rota`. Os dados normais de demonstração foram preservados.

- Plano atualizado: `docs/plano_testes.docx`.
- Resultados reais do MySQL: `resultados.docx`.
- Capturas atuais: `docs/testes/cenario1.png`, `cenario2.png` e `cenario3.png`.
- Identidade do servidor e dados conferidos: `docs/testes/execucao.json`.
- Confirmação da conexão PHP: `docs/testes/conexao-php.json`.
- Importação dos bancos: `docs/testes/mysql-conexao.json`.
- Reexecução: `tests/README.md`. Python e Playwright são ferramentas opcionais de teste.

O histórico do MariaDB foi arquivado em `docs/historico-mariadb/` e `docs/testes/mariadb-anterior/`. Ele não integra a contagem nem as evidências atuais do MySQL. O código-base citado no enunciado não foi fornecido; a implementação original foi criada do zero seguindo os anexos.

## Git e pull request

Repositório local independente, com branch `main` e desenvolvimento em `rafael-oliveira-de-souza`. Os commits podem ser consultados com `git log --oneline --all --graph`. O e-mail de autoria local `aluno@example.test` é demonstrativo; configure seu e-mail antes de próximos commits.

Não foi fornecida URL de repositório GitHub nem acesso autenticado. O PR remoto não foi aberto. O título e a descrição estão preparados em `.github/PR_TITULO.txt` e `.github/PULL_REQUEST.md`; siga `docs/PUBLICAR_GITHUB.md` para publicar as duas branches e abrir o PR para main.
