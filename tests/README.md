# Reexecutar a verificação

Os testes são opcionais para executar o sistema. Eles usam Python 3.10+, Playwright e Google Chrome instalado. O backend da aplicação não depende dessas ferramentas.

```powershell
python -m pip install playwright
$env:PHP_EXE = 'C:/xampp/php/php.exe'
$env:DB_PORT = '3306'
python tests/executar.py
```

Configure também DB_HOST, DB_USER e DB_PASS se necessário. O script recria exclusivamente o banco de teste cujo nome termina em `_teste`, importa o SQL, inicia um servidor PHP temporário, executa o fluxo no navegador e confere o banco diretamente. O banco normal de demonstração não é alterado. Para Edge, defina BROWSER_CHANNEL=msedge.

Os arquivos docs/testes/execucao.json, servidor.log e imagens são sobrescritos com a nova execução. Os DOCX correspondem à execução entregue e devem ser atualizados após novos testes. banco_teste.php só executa no PHP CLI.

## Apache

Com o projeto já instalado no Apache do XAMPP, a verificação HTTP complementar pode ser reexecutada:

```powershell
$env:APP_URL = 'http://localhost/oficina-rafael/'
python tests/apache.py
```

Esse script não recria o banco. Confere acesso, login, consultas e bloqueio dos arquivos privados; grava apache.json e apache.png. Para conferir também a independência entre dois sistemas, defina OUTRO_URL e OUTRO_EMAIL com a URL e a conta de demonstração do segundo projeto. Sem essas duas variáveis, apenas os 13 testes do projeto atual são executados. A execução entregue incluiu esse teste adicional, totalizando 14 verificações no Apache.
