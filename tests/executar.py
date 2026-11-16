import concurrent.futures
import json
import os
from pathlib import Path
import shutil
import socket
import subprocess
import time
from datetime import datetime, timezone, timedelta
from urllib.request import urlopen

from playwright.sync_api import sync_playwright

ROOT = Path(__file__).resolve().parent.parent
OUT = ROOT / 'docs' / 'testes'
OUT.mkdir(parents=True, exist_ok=True)
PHP = os.environ.get('PHP_EXE') or shutil.which('php')
ENV = os.environ.copy()
ENV['DB_NAME'] = 'oficina_rota_teste'
DOMAIN = 'rota.test'
RESULTS = []

def db(sql=None, params=None):
    payload = {'acao': 'consulta', 'sql': sql, 'params': params or []} if sql else {'acao': 'preparar'}
    proc = subprocess.run([PHP, str(ROOT / 'tests' / 'banco_teste.php')], input=json.dumps(payload), text=True, encoding='utf-8', capture_output=True, env=ENV)
    if proc.returncode:
        raise RuntimeError(proc.stderr)
    return json.loads(proc.stdout)

def check(title, condition, details):
    RESULTS.append({'teste': title, 'resultado': 'APROVADO' if condition else 'REPROVADO', 'detalhes': details})
    if not condition:
        raise AssertionError(title + ': ' + str(details))

def scalar(sql, params=None):
    return next(iter(db(sql, params)[0].values()))

def fill_order(page, start, end, description, mechanic='2', vehicle='1'):
    page.locator('[name=veiculo_id]').select_option(vehicle)
    page.locator('[name=mecanico_id]').select_option(mechanic)
    page.locator('[name=inicio]').fill(start)
    page.locator('[name=fim]').fill(end)
    page.locator('[name=descricao]').fill(description)

def main():
    info = db()
    lint = []
    for file in ROOT.rglob('*.php'):
        r = subprocess.run([PHP, '-l', str(file)], capture_output=True, text=True)
        lint.append({'arquivo': str(file.relative_to(ROOT)), 'status': r.returncode})
    check('Sintaxe PHP', all(x['status'] == 0 for x in lint), lint)
    with socket.socket() as sock:
        sock.bind(('127.0.0.1', 0))
        port = sock.getsockname()[1]
    base = f'http://127.0.0.1:{port}/index.php'
    server_log = (OUT / 'servidor.log').open('w', encoding='utf-8')
    server = subprocess.Popen([PHP, '-S', f'127.0.0.1:{port}', '-t', str(ROOT / 'public')], env=ENV, stdout=server_log, stderr=server_log)
    start = time.monotonic()
    try:
        while True:
            try:
                urlopen(base, timeout=1).close()
                break
            except Exception:
                if time.monotonic() - start > 15:
                    raise RuntimeError('O servidor PHP não iniciou. Consulte servidor.log.')
                time.sleep(.2)
        with sync_playwright() as p:
            browser = p.chromium.launch(channel=os.environ.get('BROWSER_CHANNEL', 'chrome'), headless=True)
            ctx = browser.new_context(viewport={'width': 1440, 'height': 1000}, timezone_id='America/Sao_Paulo', locale='pt-BR')
            page = ctx.new_page()
            errors = []
            page.on('pageerror', lambda exc: errors.append(str(exc)))
            page.goto(base)
            page.screenshot(path=str(OUT / 'login.png'), full_page=True)
            page.locator('[name=email]').fill('admin@' + DOMAIN)
            page.locator('[name=senha]').fill('Oficina@123')
            page.get_by_role('button', name='Entrar no sistema').click()
            check('Login do administrador', page.get_by_role('heading', name='Ordens de serviço', exact=True).count() == 1, page.url)
            page.get_by_role('link', name='Nova ordem').click()
            fill_order(page, '2026-11-18T09:00', '2026-11-18T10:00', 'Cenário 1: revisão de demonstração')
            page.get_by_role('button', name='Salvar ordem de serviço').click()
            a = int(page.locator('[name=id]').input_value())
            record = db('SELECT * FROM ordens WHERE id = ?', [a])[0]
            check('Cenário 1 — cadastro permitido', record['inicio'] == '2026-11-18 09:00:00' and page.get_by_role('status').count() == 1, {'id': a, 'registro_banco': record, 'evidencia': 'cenario1.png'})
            page.screenshot(path=str(OUT / 'cenario1.png'), full_page=True)
            audit = db('SELECT ordem_numero, usuario_id, acao, criado_em FROM operacoes WHERE ordem_numero = ?', [a])
            check('Auditoria do cadastro', len(audit) == 1 and audit[0]['usuario_id'] == 1 and audit[0]['acao'] == 'cadastro', audit)
            page.goto(base + '?pagina=ordens&novo=1')
            fill_order(page, '2026-11-18T09:30', '2026-11-18T10:30', 'Cenário 2: conflito parcial')
            total = scalar('SELECT COUNT(*) FROM ordens')
            with page.expect_response(lambda r: r.request.method == 'POST') as resp:
                page.get_by_role('button', name='Salvar ordem de serviço').click()
            check('Cenário 2 — bloqueio de conflito no cadastro', resp.value.status == 409 and page.get_by_role('alert').count() == 1 and scalar('SELECT COUNT(*) FROM ordens') == total, {'http': resp.value.status, 'mensagem': page.get_by_role('alert').inner_text(), 'total_antes_depois': [total, scalar('SELECT COUNT(*) FROM ordens')], 'evidencia': 'cenario2.png'})
            page.screenshot(path=str(OUT / 'cenario2.png'), full_page=True)
            page.goto(base + '?pagina=ordens&novo=1')
            fill_order(page, '2026-11-18T11:00', '2026-11-18T12:00', 'Ordem auxiliar para alteração')
            page.get_by_role('button', name='Salvar ordem de serviço').click()
            b = int(page.locator('[name=id]').input_value())
            before = db('SELECT * FROM ordens WHERE id = ?', [b])[0]
            page.locator('[name=inicio]').fill('2026-11-18T09:45')
            page.locator('[name=fim]').fill('2026-11-18T10:15')
            with page.expect_response(lambda r: r.request.method == 'POST') as resp:
                page.get_by_role('button', name='Salvar ordem de serviço').click()
            after = db('SELECT * FROM ordens WHERE id = ?', [b])[0]
            check('Cenário 3 — alteração com conflito', resp.value.status == 409 and before == after, {'http': resp.value.status, 'registro_antes': before, 'registro_depois': after, 'mensagem': page.get_by_role('alert').inner_text(), 'evidencia': 'cenario3.png'})
            page.screenshot(path=str(OUT / 'cenario3.png'), full_page=True)
            check('Falhas não geram auditoria de sucesso', scalar('SELECT COUNT(*) FROM operacoes WHERE ordem_numero = ?', [b]) == 1, {'ordem': b, 'operacoes': scalar('SELECT COUNT(*) FROM operacoes WHERE ordem_numero = ?', [b])})
            page.goto(base + f'?pagina=ordens&editar={b}')
            page.locator('[name=observacoes]').fill('Atualização sem mudança de horário.')
            page.get_by_role('button', name='Salvar ordem de serviço').click()
            check('Atualização no próprio horário', page.get_by_role('status').count() == 1, db('SELECT inicio, fim, observacoes FROM ordens WHERE id = ?', [b]))
            page.goto(base + '?pagina=ordens&novo=1')
            fill_order(page, '2026-11-18T10:00', '2026-11-18T11:00', 'Atendimento contíguo permitido')
            page.get_by_role('button', name='Salvar ordem de serviço').click()
            contiguous = int(page.locator('[name=id]').input_value())
            check('Intervalos contíguos permitidos', page.get_by_role('status').count() == 1, {'ordem': contiguous})
            page.goto(base + '?pagina=ordens&busca=Mariana&data=2026-11-18')
            rows = page.locator('tbody tr')
            check('Filtros combinados no servidor', rows.count() == 3 and all('Mariana Costa' in s for s in rows.all_text_contents()), {'linhas': rows.count(), 'url': page.url})
            page.goto(base + '?pagina=ordens')
            check('Paginação do backend', page.locator('tbody tr').count() == 5 and page.get_by_role('link', name='Próxima').count() == 1, {'linhas_primeira_pagina': page.locator('tbody tr').count()})
            ids_first = page.locator('tbody .numero').all_text_contents()
            page.get_by_role('link', name='Próxima').click()
            ids_second = page.locator('tbody .numero').all_text_contents()
            check('Página seguinte sem duplicação', bool(ids_second) and not set(ids_first) & set(ids_second), {'primeira': ids_first, 'segunda': ids_second})
            page.goto(base + '?pagina=agenda&data=2026-11-18')
            check('Agenda na data selecionada', page.locator('tbody tr').count() == 3, {'linhas': page.locator('tbody tr').count()})
            token = page.locator('[name=csrf]').first.input_value()
            invalid = ctx.request.post(base + '?pagina=ordens', form={'csrf': 'invalido', 'acao': 'excluir', 'id': str(a)})
            check('Bloqueio de CSRF', invalid.status == 403 and scalar('SELECT COUNT(*) FROM ordens WHERE id = ?', [a]) == 1, {'http': invalid.status})
            invalid_interval = ctx.request.post(base + '?pagina=ordens&novo=1', form={'csrf': token, 'acao': 'salvar_ordem', 'id': '0', 'veiculo_id': '1', 'mecanico_id': '2', 'inicio': '2026-11-18T13:00', 'fim': '2026-11-18T12:00', 'descricao': 'Intervalo inválido', 'situacao': 'agendada'})
            check('Validação de duração no backend', invalid_interval.status == 422, {'http': invalid_interval.status})
            page.goto(base + '?pagina=clientes&novo=1')
            page.locator('[name=nome]').fill('Cliente <script>alert(1)</script>')
            page.locator('[name=telefone]').fill('(11) 98888-9999')
            page.locator('[name=email]').fill('teste@example.test')
            page.get_by_role('button', name='Salvar registro').click()
            new_client = scalar("SELECT id FROM clientes WHERE email = 'teste@example.test'")
            check('Cadastro de cliente e escape HTML', page.locator('script').count() == 1 and page.get_by_text('Cliente <script>alert(1)</script>', exact=True).count() == 1, {'id': new_client, 'texto_exibido': True})
            page.goto(base + f'?pagina=clientes&editar={new_client}')
            page.locator('[name=nome]').fill('Cliente de teste atualizado')
            page.get_by_role('button', name='Salvar registro').click()
            check('Edição de cliente', scalar('SELECT nome FROM clientes WHERE id = ?', [new_client]) == 'Cliente de teste atualizado', {'id': new_client})
            page.goto(base + '?pagina=veiculos&novo=1')
            page.locator('[name=cliente_id]').select_option(str(new_client))
            for name, value in {'placa': 'TST1A23', 'marca': 'Fiat', 'modelo': 'Uno', 'ano': '2017'}.items():
                page.locator(f'[name={name}]').fill(value)
            page.get_by_role('button', name='Salvar registro').click()
            vehicle = scalar("SELECT id FROM veiculos WHERE placa = 'TST1A23'")
            check('Cadastro de veículo vinculado', scalar('SELECT cliente_id FROM veiculos WHERE id = ?', [vehicle]) == new_client, {'veiculo': vehicle, 'cliente': new_client})
            page.goto(base + f'?pagina=veiculos&editar={vehicle}')
            page.locator('[name=modelo]').fill('Uno Mille')
            page.get_by_role('button', name='Salvar registro').click()
            check('Edição de veículo', scalar('SELECT modelo FROM veiculos WHERE id = ?', [vehicle]) == 'Uno Mille', {'veiculo': vehicle})
            page.goto(base + '?pagina=clientes')
            token = page.locator('[name=csrf]').first.input_value()
            linked = ctx.request.post(base + '?pagina=clientes', form={'csrf': token, 'acao': 'excluir', 'id': str(new_client)})
            check('Proteção de exclusão com vínculo', linked.status == 422 and scalar('SELECT COUNT(*) FROM clientes WHERE id = ?', [new_client]) == 1, {'http': linked.status})
            for kind, ident in [('veiculos', vehicle), ('clientes', new_client)]:
                deleted = ctx.request.post(base + '?pagina=' + kind, form={'csrf': token, 'acao': 'excluir', 'id': str(ident)})
                check('Exclusão administrativa de ' + kind, deleted.ok and scalar(f'SELECT COUNT(*) FROM {kind} WHERE id = ?', [ident]) == 0, {'id': ident, 'http_final': deleted.status})
            page.goto(base + '?pagina=usuarios')
            page.locator('[name=nome]').fill('Mecânico de teste')
            page.locator('[name=email]').fill('novo@' + DOMAIN)
            page.locator('[name=senha]').fill('Oficina@123')
            page.locator('[name=perfil]').select_option('mecanico')
            page.get_by_role('button', name='Cadastrar usuário').click()
            check('Cadastro administrativo de usuário', page.get_by_role('status').count() == 1 and scalar('SELECT COUNT(*) FROM usuarios WHERE email = ?', ['novo@' + DOMAIN]) == 1, {'email': 'novo@' + DOMAIN})
            page.get_by_role('button', name='Sair', exact=True).click()
            check('Logout', page.get_by_role('button', name='Entrar no sistema').count() == 1, page.url)
            page.goto(base + '?pagina=ordens')
            check('Rota privada sem sessão', 'pagina=login' in page.url, page.url)
            page.locator('[name=email]').fill("' OR 1=1 --")
            page.locator('[name=senha]').fill('incorreta')
            bad_token = page.locator('[name=csrf]').input_value()
            bad_login = ctx.request.post(base + '?pagina=login', form={'csrf': bad_token, 'acao': 'login', 'email': "' OR 1=1 --", 'senha': 'incorreta'})
            check('Login não aceita injeção SQL', bad_login.status == 422 and 'E-mail ou senha incorretos.' in bad_login.text(), {'http': bad_login.status})
            page.locator('[name=email]').fill('mecanico@' + DOMAIN)
            page.locator('[name=senha]').fill('Oficina@123')
            page.get_by_role('button', name='Entrar no sistema').click()
            check('Login do mecânico', page.get_by_role('link', name='Usuários', exact=True).count() == 0, page.url)
            token = page.locator('[name=csrf]').first.input_value()
            for kind in ['ordens', 'clientes', 'veiculos']:
                denied = ctx.request.post(base + '?pagina=' + kind, form={'csrf': token, 'acao': 'excluir', 'id': '1'})
                check('Mecânico não exclui ' + kind, denied.status == 403, {'http': denied.status})
            denied_create = ctx.request.post(base + '?pagina=clientes&novo=1', form={'csrf': token, 'acao': 'salvar_cadastro', 'nome': 'Proibido', 'telefone': '(11) 98888-9999', 'id': '0'})
            check('Mecânico não cadastra cliente via POST', denied_create.status == 403, {'http': denied_create.status})
            for route in ['usuarios', 'ordens&editar=3']:
                response = page.goto(base + '?pagina=' + route)
                check('Mecânico não acessa ' + route, response.status == 403, {'http': response.status})
            for route, hidden in [('clientes', 'Juliana Ribeiro'), ('veiculos', 'HIJ7K89'), ('ordens', 'Alinhamento e balanceamento')]:
                page.goto(base + '?pagina=' + route)
                check('Escopo autorizado de ' + route, hidden not in page.locator('tbody').inner_text(), {'registro_de_outro_mecanico_oculto': hidden})
            page.goto(base + f'?pagina=ordens&editar={a}')
            own_before = db('SELECT * FROM ordens WHERE id = ?', [a])[0]
            token = page.locator('[name=csrf]').first.input_value()
            update = ctx.request.post(base + f'?pagina=ordens&editar={a}', form={'csrf': token, 'acao': 'salvar_ordem', 'id': str(a), 'situacao': 'em_andamento', 'observacoes': 'Diagnóstico registrado pelo mecânico.', 'mecanico_id': '3', 'veiculo_id': '3', 'inicio': '2026-11-18T01:00', 'fim': '2026-11-18T23:00', 'descricao': 'Alteração não autorizada'})
            own_after = db('SELECT * FROM ordens WHERE id = ?', [a])[0]
            protected = ['mecanico_id', 'veiculo_id', 'inicio', 'fim', 'descricao']
            check('Mecânico atualiza execução com campos protegidos', update.ok and own_after['situacao'] == 'em_andamento' and own_after['observacoes'] == 'Diagnóstico registrado pelo mecânico.' and all(own_before[k] == own_after[k] for k in protected), {'registro_antes': own_before, 'registro_depois': own_after})
            invalid_mechanic = ctx.request.post(base + f'?pagina=ordens&editar={a}', form={'csrf': token, 'acao': 'salvar_ordem', 'id': str(a), 'situacao': 'cancelada', 'observacoes': 'Não deve cancelar'})
            check('Formulário inválido do mecânico retorna erro sem falhar', invalid_mechanic.status == 403 and 'Situação não permitida' in invalid_mechanic.text() and 'Fatal error' not in invalid_mechanic.text() and scalar('SELECT situacao FROM ordens WHERE id = ?', [a]) == 'em_andamento', {'http': invalid_mechanic.status})
            invalid_csrf_edit = ctx.request.post(base + f'?pagina=ordens&editar={a}', form={'csrf': 'invalido', 'acao': 'salvar_ordem', 'id': str(a)})
            check('Sessão de formulário inválida preserva a tela da ordem', invalid_csrf_edit.status == 403 and 'Sua sessão do formulário expirou' in invalid_csrf_edit.text() and 'Fatal error' not in invalid_csrf_edit.text(), {'http': invalid_csrf_edit.status})
            page.goto(base + '?pagina=ordens')
            page.set_viewport_size({'width': 390, 'height': 844})
            page.screenshot(path=str(OUT / 'responsivo.png'), full_page=True)
            overflow = page.evaluate('document.documentElement.scrollWidth > window.innerWidth')
            check('Layout a 390 px', not overflow, {'largura': 390, 'overflow_documento': overflow, 'evidencia': 'responsivo.png'})
            page.set_viewport_size({'width': 1440, 'height': 1000})
            check('JavaScript sem erros no navegador', not errors, errors)

            page.get_by_role('button', name='Sair', exact=True).click()
            page.locator('[name=email]').fill('admin@' + DOMAIN)
            page.locator('[name=senha]').fill('Oficina@123')
            page.get_by_role('button', name='Entrar no sistema').click()
            token = page.locator('[name=csrf]').first.input_value()
            def post_order(start, end, mechanic='2', status='agendada', ident='0', description='Teste adicional de agenda'):
                return ctx.request.post(base + '?pagina=ordens' + ('&editar=' + ident if ident != '0' else '&novo=1'), form={'csrf': token, 'acao': 'salvar_ordem', 'id': ident, 'veiculo_id': '1', 'mecanico_id': mechanic, 'inicio': start, 'fim': end, 'descricao': description, 'situacao': status})
            malformed = ctx.request.post(base + '?pagina=ordens&novo=1', form={'csrf': token, 'acao': 'salvar_ordem', 'id': '0', 'veiculo_id[]': '1', 'mecanico_id[]': '2', 'inicio': '2026-11-18T13:00', 'fim': '2026-11-18T14:00', 'descricao[]': 'Campo em formato inválido', 'situacao': 'agendada'})
            check('Formato inválido de campos rejeitado sem avisos PHP', malformed.status == 422 and 'Warning:' not in malformed.text() and 'Fatal error' not in malformed.text(), {'http': malformed.status})
            same = post_order('2026-11-18T09:00', '2026-11-18T10:00')
            envelop = post_order('2026-11-18T08:00', '2026-11-18T13:00')
            inside = post_order('2026-11-18T09:15', '2026-11-18T09:45')
            check('Conflitos idênticos, internos e envolventes', all(r.status == 409 for r in [same, envelop, inside]), {'http': [r.status for r in [same, envelop, inside]]})
            free = post_order('2026-11-18T09:00', '2026-11-18T10:00', mechanic='3')
            other = scalar("SELECT id FROM ordens WHERE mecanico_id = 3 AND inicio = '2026-11-18 09:00:00'")
            check('Mesmo horário com outro mecânico', free.ok, {'ordem': other, 'http_final': free.status})
            change = post_order('2026-11-18T09:00', '2026-11-18T10:00', mechanic='2', ident=str(other))
            check('Troca de mecânico com conflito preserva responsável', change.status == 409 and scalar('SELECT mecanico_id FROM ordens WHERE id = ?', [other]) == 3, {'http': change.status})
            cancel = post_order('2026-11-18T09:00', '2026-11-18T10:00', mechanic='3', status='cancelada', ident=str(other))
            released = post_order('2026-11-18T09:00', '2026-11-18T10:00', mechanic='3')
            check('Cancelamento libera o intervalo', cancel.ok and released.ok, {'cancelamento_http_final': cancel.status, 'novo_http_final': released.status})
            reactivate = post_order('2026-11-18T09:00', '2026-11-18T10:00', mechanic='3', ident=str(other))
            check('Reativar ordem verifica conflito', reactivate.status == 409 and scalar('SELECT situacao FROM ordens WHERE id = ?', [other]) == 'cancelada', {'http': reactivate.status})
            cross = post_order('2026-11-19T23:30', '2026-11-20T00:30', mechanic='3', description='Atendimento atravessando a meia-noite')
            page.goto(base + '?pagina=agenda&data=2026-11-20')
            check('Agenda inclui intervalo do dia anterior', cross.ok and 'Atendimento atravessando a meia-noite' in page.locator('tbody').inner_text(), {'http_final': cross.status, 'dia_consultado': '2026-11-20'})
            deleted = ctx.request.post(base + '?pagina=ordens', form={'csrf': token, 'acao': 'excluir', 'id': str(contiguous)})
            check('Exclusão de ordem mantém auditoria', deleted.ok and scalar('SELECT COUNT(*) FROM ordens WHERE id = ?', [contiguous]) == 0 and scalar("SELECT COUNT(*) FROM operacoes WHERE ordem_numero = ? AND acao = 'exclusao'", [contiguous]) == 1, db('SELECT acao, usuario_id, criado_em FROM operacoes WHERE ordem_numero = ?', [contiguous]))
            response = page.goto(base + '?pagina=ordens&data=2026-02-30')
            check('Data inválida rejeitada no servidor', response.status == 422, {'http': response.status})
            page.goto(base + '?pagina=usuarios')
            token = page.locator('[name=csrf]').first.input_value()
            duplicate = ctx.request.post(base + '?pagina=usuarios', form={'csrf': token, 'acao': 'cadastrar_usuario', 'nome': 'Usuário duplicado', 'email': 'admin@' + DOMAIN, 'senha': 'Oficina@123', 'perfil': 'administrador'})
            check('E-mail duplicado é bloqueado', duplicate.status == 422, {'http': duplicate.status})
            spaced = ctx.request.post(base + '?pagina=usuarios', form={'csrf': token, 'acao': 'cadastrar_usuario', 'nome': 'Usuário com senha de teste', 'email': 'espacos@' + DOMAIN, 'senha': ' Senha@123 ', 'perfil': 'mecanico'})
            page.get_by_role('button', name='Sair', exact=True).click()
            page.locator('[name=email]').fill('espacos@' + DOMAIN)
            page.locator('[name=senha]').fill(' Senha@123 ')
            page.get_by_role('button', name='Entrar no sistema').click()
            check('Senha preserva espaços informados', spaced.ok and page.get_by_role('button', name='Sair', exact=True).count() == 1, {'cadastro_http_final': spaced.status})
            page.goto(base + '?pagina=ordens')
            check('Mecânico novo sem ordens não consulta registros de terceiros', 'Nenhum registro encontrado' in page.locator('tbody').inner_text(), {'mensagem': page.locator('tbody').inner_text()})
            page.goto(base + '?pagina=login')
            start_at = time.time() + 1
            payload = {'iniciar_em': start_at, 'ordem': {'id': '0', 'veiculo_id': '1', 'mecanico_id': '2', 'inicio': '2026-11-21T09:00', 'fim': '2026-11-21T10:00', 'descricao': 'Concorrência entre duas conexões', 'observacoes': '', 'situacao': 'agendada'}}
            def run_concurrent(_):
                result = subprocess.run([PHP, str(ROOT / 'tests/concorrencia.php')], input=json.dumps(payload), text=True, encoding='utf-8', capture_output=True, env=ENV)
                if result.returncode:
                    raise RuntimeError(result.stderr)
                return json.loads(result.stdout)
            with concurrent.futures.ThreadPoolExecutor(max_workers=2) as pool:
                concurrent_results = list(pool.map(run_concurrent, range(2)))
            check('Concorrência em conexões independentes', sorted(r['status'] for r in concurrent_results) == [200, 409] and scalar("SELECT COUNT(*) FROM ordens WHERE mecanico_id = 2 AND inicio = '2026-11-21 09:00:00'") == 1, concurrent_results)

            browser.close()
    finally:
        server.terminate()
        server.wait(timeout=10)
        server_log.close()
        report = {'projeto': ROOT.name, 'executado_em': datetime.now(timezone(timedelta(hours=-3))).isoformat(), 'fuso_agendamento': 'America/Sao_Paulo', 'banco': ENV['DB_NAME'], 'servidor_banco': info, 'php': subprocess.check_output([PHP, '-v'], text=True).splitlines()[0], 'navegador': 'Chrome headless via Playwright', 'testes': RESULTS}
        (OUT / 'execucao.json').write_text(json.dumps(report, ensure_ascii=False, indent=2), encoding='utf-8')
    print(f'{ROOT.name}: {len(RESULTS)} verificações aprovadas. Evidências em {OUT}')

if __name__ == '__main__':
    main()
