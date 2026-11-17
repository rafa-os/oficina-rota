import json
import os
from pathlib import Path
from datetime import datetime, timezone, timedelta
from playwright.sync_api import sync_playwright

ROOT = Path(__file__).resolve().parent.parent
BASE = os.environ.get('APP_URL', 'http://localhost/oficina-rafael/').rstrip('/') + '/'
DOMAIN = 'rota.test'
SITE = 'Oficina Rota'
OUT = ROOT / 'docs/testes'
OUT.mkdir(parents=True, exist_ok=True)
report = {'executado_em': datetime.now(timezone(timedelta(hours=-3))).isoformat(), 'testes': []}

def check(name, condition, details):
    report['testes'].append({'teste': name, 'resultado': 'APROVADO' if condition else 'REPROVADO', 'detalhes': details})
    if not condition:
        raise AssertionError((name, details))

def login(page, email):
    page.locator('[name=email]').fill(email)
    page.locator('[name=senha]').fill('Oficina@123')
    page.get_by_role('button', name='Entrar').click()

try:
    with sync_playwright() as p:
        browser = p.chromium.launch(channel=os.environ.get('BROWSER_CHANNEL', 'chrome'), headless=True)
        ctx = browser.new_context(viewport={'width': 1440, 'height': 1000}, locale='pt-BR', timezone_id='America/Sao_Paulo')
        page = ctx.new_page()
        response = page.goto(BASE)
        report['servidor'] = response.headers.get('server', 'Não informado no cabeçalho HTTP')
        check('Página inicial pelo Apache', response.status == 200 and SITE in page.title(), {'http_final': response.status, 'url': page.url})
        for private in ['.git/HEAD', '.github/PULL_REQUEST.md', 'database/banco.sql', 'config/banco.php', 'docs/REQUISITOS.md', 'tests/banco_teste.php']:
            denied = ctx.request.get(BASE + private)
            check('Bloqueio HTTP de ' + private, denied.status == 403, {'http': denied.status})
        asset = ctx.request.get(BASE + 'public/assets/estilo.css')
        check('CSS público', asset.status == 200, {'http': asset.status})
        login(page, 'admin@' + DOMAIN)
        check('Login pelo módulo PHP do Apache', page.get_by_role('heading', name='Ordens de serviço', exact=True).count() == 1, {'url': page.url})
        font = page.evaluate('getComputedStyle(document.body).fontFamily')
        check('Fonte Arial no navegador', font.startswith('Arial'), {'fonte': font})
        page.screenshot(path=str(OUT / 'apache.png'), full_page=True)
        for route in ['clientes', 'veiculos', 'agenda', 'usuarios']:
            response = page.goto(BASE + 'public/index.php?pagina=' + route)
            check('Consulta Apache: ' + route, response.status == 200, {'http': response.status})
            page.screenshot(path=str(OUT / (route + '.png')), full_page=True)
        for route in ['clientes', 'veiculos', 'ordens']:
            page.goto(BASE + 'public/index.php?pagina=' + route + '&novo=1')
            page.screenshot(path=str(OUT / ('novo-' + route + '.png')), full_page=True)
        for width in [1440, 768, 390]:
            page.set_viewport_size({'width': width, 'height': 900})
            page.goto(BASE + 'public/index.php?pagina=ordens')
            overflow = page.evaluate('document.documentElement.scrollWidth > window.innerWidth')
            check('Tela sem transbordamento a ' + str(width) + ' px', not overflow, {'largura': width, 'transbordamento': overflow})
        page.screenshot(path=str(OUT / 'tela-celular.png'), full_page=True)
        page.set_viewport_size({'width': 1440, 'height': 1000})
        other_url = os.environ.get('OUTRO_URL')
        other_email = os.environ.get('OUTRO_EMAIL')
        if other_url and other_email:
            other = ctx.new_page()
            other.goto(other_url)
            login(other, other_email)
            page.reload()
            own_logged = page.get_by_role('button', name='Sair', exact=True).count() == 1
            page.get_by_role('button', name='Sair', exact=True).click()
            other.reload()
            other_logged = other.get_by_role('button', name='Sair', exact=True).count() == 1
            check('Sessões independentes no mesmo navegador', own_logged and other_logged, {'logout_de': SITE, 'outro_login_preservado': other_logged})
            other.get_by_role('button', name='Sair', exact=True).click()
        else:
            page.get_by_role('button', name='Sair', exact=True).click()
        report['cookies'] = [{'nome': c['name'], 'httpOnly': c['httpOnly'], 'sameSite': c['sameSite']} for c in ctx.cookies()]
        browser.close()
finally:
    (OUT / 'apache.json').write_text(json.dumps(report, ensure_ascii=False, indent=2), encoding='utf-8')
print(ROOT.name, len(report['testes']), 'verificações Apache aprovadas')
