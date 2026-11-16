# Publicar e abrir o pull request

O Git local já está pronto. Não há remoto configurado nem PR remoto aberto. Estes passos se destinam a um repositório novo e vazio do GitHub.

1. Entre no GitHub e crie um repositório para `oficina-rafael` sem README, licença ou .gitignore gerados no site.
2. No PowerShell, abra a pasta deste projeto e execute os comandos abaixo, substituindo os dois textos indicados:

```powershell
git config user.email 'SEU_EMAIL_DE_AUTORIA'
$repoUrl = 'COLE_AQUI_A_URL_HTTPS_DO_REPOSITORIO'
git remote add origin $repoUrl
git push -u origin main
git push -u origin rafael-oliveira-de-souza
```

3. Complete a autenticação solicitada pelo GitHub. Na página do repositório, use **Compare & pull request**.
4. Selecione **base: main** e **compare: rafael-oliveira-de-souza**.
5. Copie o título de `.github/PR_TITULO.txt` e o texto de `.github/PULL_REQUEST.md`. Abra o PR e confira se os arquivos e evidências estão listados.

Se preferir GitHub CLI, após instalar e autenticar `gh`, execute nesta pasta:

```powershell
gh pr create --base main --head rafael-oliveira-de-souza --title 'feat: implementa Oficina Rota com agenda e permissoes' --body-file .github/PULL_REQUEST.md
```

Os links reais do repositório e do PR só existirão após essa publicação. Se a professora disponibilizar um repositório existente, utilize a main desse repositório como base da branch e transfira os arquivos desta entrega antes de criar o PR.
