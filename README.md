# Oficina Rota

Sistema de gestão de oficina desenvolvido por **Rafael Oliveira de Souza** para a atividade da Prof.ª Noemi. HTML, CSS, JavaScript puro, PHP e PDO com MySQL/MariaDB. O visual é azul, com navegação superior. O projeto funciona sozinho e usa o banco `oficina_rota`.

## Instalar no XAMPP

1. Use XAMPP com PHP 8.1 ou superior e MariaDB 10.4+ ou MySQL 8+. O teste real usou PHP 8.2.12 e MariaDB 10.4.32.
2. Copie a pasta `oficina-rafael` completa para `C:\xampp\htdocs\oficina-rafael`.
3. Inicie **Apache** e **MySQL** no painel do XAMPP. Se outra instância já usa a porta 3306, utilize a porta configurada para a instância escolhida.
4. Abra `http://localhost/phpmyadmin`, vá a **Importar** e selecione `database/banco.sql`. O script cria o banco `oficina_rota`, as tabelas e os dados fictícios. Importe uma vez em um banco novo; o script não apaga dados existentes.
5. Confira `config/banco.php`: host `127.0.0.1`, porta `3306`, banco `oficina_rota`, usuário `root`, senha vazia são os padrões locais do XAMPP. Ajuste se sua instalação usar outros valores. Também são aceitas as variáveis DB_HOST, DB_PORT, DB_NAME, DB_USER e DB_PASS.
6. Acesse **http://localhost/oficina-rafael/public/**. A raiz `http://localhost/oficina-rafael/` também redireciona para essa página.

O Apache deve aceitar `.htaccess` (AllowOverride All, padrão do XAMPP). As pastas internas ficam bloqueadas para acesso HTTP. Em hospedagem configurável, use `public/` como DocumentRoot. Para um teste local via terminal, a partir desta pasta:

```powershell
& 'C:/xampp/php/php.exe' -S 127.0.0.1:8001 -t public
```

Abra `http://127.0.0.1:8001/`. O banco precisa estar iniciado e importado também nessa opção. Use o PHP do XAMPP com a extensão `pdo_mysql` habilitada.

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
| config/banco.php | Conexão local e variáveis de ambiente |
| app/bootstrap.php | Inicialização, sessão e conexão PDO |
| app/controllers/ | Fluxo das requisições e autorização |
| app/models/Oficina.php | Consultas, validações, agenda e auditoria |
| app/views/ | Formulários, listas e layout |
| database/banco.sql | Esquema e dados fictícios |
| docs/REQUISITOS.md | Mapeamento dos requisitos e decisões |
| tests/ | Automação opcional de verificação |

Não há bibliotecas, frameworks ou gerenciadores de pacotes necessários para rodar o sistema.

## Testes e evidências

Foram executadas **68 verificações aprovadas**: 54 no navegador/servidor PHP e conexões PDO, e 14 adicionais pelo Apache do XAMPP. Os testes usaram o banco isolado `oficina_rota_teste` em uma instância MariaDB na porta 3307. Não modificaram a carga SQL entregue nem o MySQL que já estava rodando no computador.

- Plano: `docs/plano_testes.docx`.
- Resultados efetivos: `resultados.docx`.
- Três evidências obrigatórias: `docs/testes/cenario1.png`, `cenario2.png` e `cenario3.png`.
- Dados brutos e capturas adicionais: `docs/testes/`.
- Reexecução: `tests/README.md`. As ferramentas Python e Playwright são usadas somente nos testes.

O código-base mencionado no enunciado não foi fornecido; a implementação foi criada do zero, seguindo os dois anexos. MySQL 8 não foi executado neste ambiente; a verificação de banco foi feita com MariaDB.

## Git e pull request

Repositório local independente, com branch `main` e desenvolvimento em `rafael-oliveira-de-souza`. Os commits podem ser consultados com `git log --oneline --all --graph`. O e-mail de autoria local `aluno@example.test` é demonstrativo; configure seu e-mail antes de próximos commits.

Não foi fornecida URL de repositório GitHub nem acesso autenticado. O PR remoto não foi aberto. O título e a descrição estão preparados em `.github/PR_TITULO.txt` e `.github/PULL_REQUEST.md`; siga `docs/PUBLICAR_GITHUB.md` para publicar as duas branches e abrir o PR para main.
