<?php
declare(strict_types=1);

class Oficina
{
    public function __construct(private PDO $db) {}

    public function consulta(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function um(string $sql, array $params = []): ?array
    {
        return $this->consulta($sql, $params)->fetch() ?: null;
    }

    public function todos(string $sql, array $params = []): array
    {
        return $this->consulta($sql, $params)->fetchAll();
    }

    public function contar(string $sql, array $params = []): int
    {
        return (int) $this->consulta($sql, $params)->fetchColumn();
    }

    private function tamanho(string $valor, int $min, int $max, string $nome): string
    {
        if (mb_strlen($valor) < $min || mb_strlen($valor) > $max) {
            throw new RegraException("$nome deve ter entre $min e $max caracteres.");
        }
        return $valor;
    }

    public function salvarCadastro(string $tipo, array $dados, array $usuario): void
    {
        admin($usuario);
        $id = filter_var(texto($dados, 'id', '0'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        if ($id === false) {
            throw new RegraException('Identificador inválido.');
        }
        if ($tipo === 'clientes') {
            $valores = [$this->tamanho(texto($dados, 'nome'), 3, 100, 'Nome'), texto($dados, 'telefone'), texto($dados, 'email')];
            if (!preg_match('/^[0-9 ()+\-]{8,20}$/', $valores[1])) {
                throw new RegraException('Informe um telefone válido, com DDD.');
            }
            if ($valores[2] !== '' && (!filter_var($valores[2], FILTER_VALIDATE_EMAIL) || strlen($valores[2]) > 150)) {
                throw new RegraException('Informe um e-mail válido.');
            }
            $colunas = 'nome = ?, telefone = ?, email = ?';
        } elseif ($tipo === 'veiculos') {
            $placa = strtoupper(str_replace('-', '', texto($dados, 'placa')));
            $ano = filter_var(texto($dados, 'ano'), FILTER_VALIDATE_INT);
            $cliente = $this->um('SELECT id FROM clientes WHERE id = ?', [texto($dados, 'cliente_id')]);
            if (!preg_match('/^[A-Z]{3}[0-9][A-Z0-9][0-9]{2}$/', $placa) || !$ano || $ano < 1900 || $ano > (int) date('Y') + 1 || !$cliente) {
                throw new RegraException('Confira a placa, o ano e o proprietário selecionado.');
            }
            $valores = [$cliente['id'], $placa, $this->tamanho(texto($dados, 'marca'), 2, 50, 'Marca'), $this->tamanho(texto($dados, 'modelo'), 2, 80, 'Modelo'), $ano];
            $colunas = 'cliente_id = ?, placa = ?, marca = ?, modelo = ?, ano = ?';
            if ($id) {
                $antigo = $this->um('SELECT cliente_id FROM veiculos WHERE id = ?', [$id]);
                if ($antigo && (int) $antigo['cliente_id'] !== (int) $cliente['id'] && $this->contar('SELECT COUNT(*) FROM ordens WHERE veiculo_id = ?', [$id])) {
                    throw new RegraException('Este veículo possui ordens. Preserve o proprietário para manter o histórico.');
                }
            }
        } else {
            throw new RegraException('Cadastro desconhecido.', 404);
        }
        if ($id) {
            if (!$this->um("SELECT id FROM $tipo WHERE id = ?", [$id])) {
                throw new RegraException('Registro não encontrado.', 404);
            }
            $this->consulta("UPDATE $tipo SET $colunas WHERE id = ?", [...$valores, $id]);
        } else {
            $this->consulta("INSERT INTO $tipo SET $colunas", $valores);
        }
    }

    public function cadastrarUsuario(array $dados, array $usuario): void
    {
        admin($usuario);
        $nome = $this->tamanho(texto($dados, 'nome'), 3, 100, 'Nome');
        $email = texto($dados, 'email');
        $perfil = texto($dados, 'perfil');
        $senha = entradaSenha($dados);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150 || !in_array($perfil, ['administrador', 'mecanico'], true)) {
            throw new RegraException('Confira o e-mail e o perfil selecionado.');
        }
        $this->tamanho($senha, 8, 72, 'Senha');
        if (strlen($senha) > 72) {
            throw new RegraException('A senha deve ter no máximo 72 bytes.');
        }
        $this->consulta('INSERT INTO usuarios (nome, email, senha, perfil) VALUES (?, ?, ?, ?)', [$nome, $email, password_hash($senha, PASSWORD_DEFAULT), $perfil]);
    }

    public function ordem(int $id, array $usuario): array
    {
        $ordem = $this->um('SELECT * FROM ordens WHERE id = ?', [$id]);
        if (!$ordem) {
            throw new RegraException('Ordem de serviço não encontrada.', 404);
        }
        if ($usuario['perfil'] === 'mecanico' && (int) $ordem['mecanico_id'] !== (int) $usuario['id']) {
            throw new RegraException('Você só pode acessar as suas ordens de serviço.', 403);
        }
        return $ordem;
    }

    private function momento(string $valor): string
    {
        $data = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $valor);
        if (!$data || $data->format('Y-m-d\TH:i') !== $valor) {
            throw new RegraException('Informe uma data e um horário válidos.');
        }
        return $data->format('Y-m-d H:i:s');
    }

    private function registrar(string $acao, int $id, array $usuario, ?array $antes, ?array $depois): void
    {
        $this->consulta('INSERT INTO operacoes (ordem_numero, usuario_id, acao, antes, depois) VALUES (?, ?, ?, ?, ?)', [$id, $usuario['id'], $acao, $antes ? json_encode($antes, JSON_UNESCAPED_UNICODE) : null, $depois ? json_encode($depois, JSON_UNESCAPED_UNICODE) : null]);
    }

    public function salvarOrdem(array $dados, array $usuario): int
    {
        $id = filter_var(texto($dados, 'id', '0'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        if ($id === false) {
            throw new RegraException('Número de ordem inválido.');
        }
        if (!$id) {
            admin($usuario);
        }
        $this->db->beginTransaction();
        try {
            $this->consulta('SELECT id FROM controle_agenda WHERE id = 1 FOR UPDATE');
            $antes = $id ? $this->ordem($id, $usuario) : null;
            if ($usuario['perfil'] === 'mecanico') {
                if ($antes['situacao'] === 'cancelada') {
                    throw new RegraException('Uma ordem cancelada só pode ser alterada pelo administrador.', 403);
                }
                $dados = ['id' => $id, 'veiculo_id' => $antes['veiculo_id'], 'mecanico_id' => $antes['mecanico_id'], 'inicio' => date('Y-m-d\TH:i', strtotime($antes['inicio'])), 'fim' => date('Y-m-d\TH:i', strtotime($antes['fim'])), 'descricao' => $antes['descricao'], 'observacoes' => texto($dados, 'observacoes'), 'situacao' => texto($dados, 'situacao')];
                if (!in_array($dados['situacao'], ['agendada', 'em_andamento', 'concluida'], true)) {
                    throw new RegraException('Situação não permitida para o mecânico.', 403);
                }
            }
            $veiculo = $this->um('SELECT id FROM veiculos WHERE id = ?', [texto($dados, 'veiculo_id')]);
            $mecanico = $this->um("SELECT id FROM usuarios WHERE id = ? AND perfil = 'mecanico' AND ativo = 1", [texto($dados, 'mecanico_id')]);
            if (!$veiculo || !$mecanico) {
                throw new RegraException('Selecione um veículo e um mecânico válidos.');
            }
            $inicio = $this->momento(texto($dados, 'inicio'));
            $fim = $this->momento(texto($dados, 'fim'));
            if ($inicio >= $fim) {
                throw new RegraException('O término deve ser posterior ao início do atendimento.');
            }
            $situacao = texto($dados, 'situacao');
            if (!array_key_exists($situacao, situacoes())) {
                throw new RegraException('Selecione uma situação válida.');
            }
            $descricao = $this->tamanho(texto($dados, 'descricao'), 5, 1000, 'Descrição');
            $observacoes = $this->tamanho(texto($dados, 'observacoes'), 0, 2000, 'Observações');
            $conflito = $this->um("SELECT id FROM ordens WHERE mecanico_id = ? AND id <> ? AND situacao <> 'cancelada' AND inicio < ? AND fim > ? LIMIT 1 FOR UPDATE", [$mecanico['id'], $id, $fim, $inicio]);
            if ($situacao !== 'cancelada' && $conflito) {
                throw new RegraException('Agendamento não permitido: o mecânico selecionado já possui uma ordem de serviço nesse horário. Selecione outro horário ou mecânico.', 409);
            }
            $params = [$veiculo['id'], $mecanico['id'], $descricao, $observacoes, $inicio, $fim, $situacao];
            if ($id) {
                $this->consulta('UPDATE ordens SET veiculo_id = ?, mecanico_id = ?, descricao = ?, observacoes = ?, inicio = ?, fim = ?, situacao = ?, atualizado_em = CURRENT_TIMESTAMP WHERE id = ?', [...$params, $id]);
            } else {
                $this->consulta('INSERT INTO ordens (veiculo_id, mecanico_id, descricao, observacoes, inicio, fim, situacao) VALUES (?, ?, ?, ?, ?, ?, ?)', $params);
                $id = (int) $this->db->lastInsertId();
            }
            $depois = $this->um('SELECT * FROM ordens WHERE id = ?', [$id]);
            $this->registrar($antes ? 'atualizacao' : 'cadastro', $id, $usuario, $antes, $depois);
            $this->db->commit();
            return $id;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function excluir(string $tipo, int $id, array $usuario): void
    {
        admin($usuario);
        if (!in_array($tipo, ['clientes', 'veiculos', 'ordens'], true) || $id < 1) {
            throw new RegraException('Registro inválido.');
        }
        $this->db->beginTransaction();
        try {
            $this->consulta('SELECT id FROM controle_agenda WHERE id = 1 FOR UPDATE');
            $registro = $this->um("SELECT * FROM $tipo WHERE id = ?", [$id]);
            if (!$registro) {
                throw new RegraException('Registro não encontrado.', 404);
            }
            if ($tipo === 'ordens') {
                $this->registrar('exclusao', $id, $usuario, $registro, null);
            }
            $this->consulta("DELETE FROM $tipo WHERE id = ?", [$id]);
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function listar(string $tipo, array $usuario, string $busca, string $data, int $pagina, bool $agenda = false): array
    {
        $params = [];
        $where = ['1 = 1'];
        $pessoal = $usuario['perfil'] === 'mecanico';
        if ($tipo === 'ordens') {
            $from = 'ordens o JOIN veiculos v ON v.id = o.veiculo_id JOIN clientes c ON c.id = v.cliente_id JOIN usuarios u ON u.id = o.mecanico_id';
            $campos = 'o.*, c.nome AS cliente, v.placa, v.modelo, u.nome AS mecanico';
            if ($pessoal) {
                $where[] = 'o.mecanico_id = ?';
                $params[] = $usuario['id'];
            }
            if ($data !== '') {
                $validada = DateTimeImmutable::createFromFormat('!Y-m-d', $data);
                if (!$validada || $validada->format('Y-m-d') !== $data) {
                    throw new RegraException('Informe uma data de filtro válida.');
                }
                if ($agenda) {
                    $where[] = "o.inicio < ? AND o.fim > ? AND o.situacao <> 'cancelada'";
                    $params[] = $validada->modify('+1 day')->format('Y-m-d');
                    $params[] = $data;
                } else {
                    $where[] = 'o.inicio >= ? AND o.inicio < ?';
                    $params[] = $data;
                    $params[] = $validada->modify('+1 day')->format('Y-m-d');
                }
            }
            $ordem = $agenda ? 'o.inicio ASC, o.id ASC' : 'o.inicio DESC, o.id DESC';
            $buscavel = 'c.nome';
        } elseif ($tipo === 'clientes') {
            $from = 'clientes c';
            $campos = 'c.*';
            $ordem = 'c.nome, c.id';
            $buscavel = 'c.nome';
            if ($pessoal) {
                $where[] = 'EXISTS (SELECT 1 FROM veiculos v JOIN ordens o ON o.veiculo_id = v.id WHERE v.cliente_id = c.id AND o.mecanico_id = ?)';
                $params[] = $usuario['id'];
            }
        } else {
            $from = 'veiculos v JOIN clientes c ON c.id = v.cliente_id';
            $campos = 'v.*, c.nome AS cliente';
            $ordem = 'v.placa, v.id';
            $buscavel = "CONCAT(v.placa, ' ', v.modelo, ' ', c.nome)";
            if ($pessoal) {
                $where[] = 'EXISTS (SELECT 1 FROM ordens o WHERE o.veiculo_id = v.id AND o.mecanico_id = ?)';
                $params[] = $usuario['id'];
            }
        }
        if ($busca !== '') {
            $where[] = "$buscavel LIKE ?";
            $params[] = '%' . mb_substr($busca, 0, 100) . '%';
        }
        $filtro = implode(' AND ', $where);
        $total = $this->contar("SELECT COUNT(*) FROM $from WHERE $filtro", $params);
        $pagina = max(1, min($pagina, max(1, (int) ceil($total / 5))));
        $offset = ($pagina - 1) * 5;
        $itens = $this->todos("SELECT $campos FROM $from WHERE $filtro ORDER BY $ordem LIMIT 5 OFFSET $offset", $params);
        return compact('itens', 'total', 'pagina');
    }
}
