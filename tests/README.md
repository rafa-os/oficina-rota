# Reexecutar no MySQL Server

Pré-requisitos: MySQL Server 8.0.16+ ativo, config/local.php com credenciais válidas, PHP com pdo_mysql, Python 3.10+, Playwright e Google Chrome. Essas ferramentas Python são usadas somente na verificação; a aplicação não depende delas.

```powershell
python -m pip install playwright
$env:PHP_EXE = 'C:/xampp/php/php.exe'
& $env:PHP_EXE tests/conexao.php
python tests/executar.py
```

A configuração de host, porta e credenciais vem de config/banco.php e config/local.php. DB_HOST, DB_PORT, DB_USER e DB_PASS podem sobrescrevê-la. A automação define apenas DB_NAME=`oficina_rota_teste`. Ela recria exclusivamente esse banco de teste, importa o SQL, inicia um servidor PHP temporário, executa o fluxo no navegador e confere a persistência diretamente. O banco normal `oficina_rota` é preservado. A conta deve ter permissões de CREATE/DROP/DDL/DML nesse banco de teste.

Antes de recriar o banco, o script consulta a versão/distribuição e recusa MariaDB. Se a conexão falhar, registra estado BLOQUEADO e zero cenários executados. As verificações adicionais conferem a collation e tentam inserir diretamente um intervalo inválido, esperando a rejeição CHECK do MySQL (erro 3819).

Os JSON, log e PNG em docs/testes são substituídos pela nova execução. Os DOCX representam a execução entregue e devem ser atualizados após novos testes. Para Edge, defina BROWSER_CHANNEL=msedge. banco_teste.php, concorrencia.php e conexao.php executam somente via PHP CLI.

## Apache

Instale o projeto conforme o README e mantenha MySQL80 em execução. Então execute:

```powershell
$env:APP_URL = 'http://localhost/oficina-rafael/'
python tests/apache.py
```

O script verifica acesso, login, consultas e proteção dos arquivos internos, sem recriar o banco. Produz apache.json e apache.png. Para verificar também sessões independentes, defina OUTRO_URL e OUTRO_EMAIL com o endereço e a conta de demonstração do outro projeto; essa opção não é necessária para usar este sistema sozinho.

Na execução entregue: 57 verificações principais e 14 pelo Apache, totalizando 71 aprovações no MySQL Server 8.0.43, porta 3306. Os testes antigos do MariaDB permanecem identificados em pastas históricas e não entram nesse total.
