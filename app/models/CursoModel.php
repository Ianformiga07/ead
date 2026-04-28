<?php
// app/models/CursoModel.php

class CursoModel extends Model {
    protected string $table = 'cursos';

    public function listar(int $offset = 0, int $limit = 20, string $busca = '', string $tipo = ''): array {
        $like   = "%$busca%";
        $where  = "WHERE (nome LIKE ? OR descricao LIKE ?)";
        $params = [$like, $like];
        if ($tipo) { $where .= " AND tipo=?"; $params[] = $tipo; }
        return $this->findAll(
            "SELECT * FROM cursos $where ORDER BY nome LIMIT ? OFFSET ?",
            [...$params, $limit, $offset]
        );
    }

    public function total(string $busca = '', string $tipo = ''): int {
        $like   = "%$busca%";
        $where  = "WHERE (nome LIKE ? OR descricao LIKE ?)";
        $params = [$like, $like];
        if ($tipo) { $where .= " AND tipo=?"; $params[] = $tipo; }
        return $this->count("SELECT COUNT(*) FROM cursos $where", $params);
    }

    public function findById(int $id): array|false {
        return $this->find("SELECT * FROM cursos WHERE id=?", [$id]);
    }

    public function criar(array $d): int {
        $this->execute(
            "INSERT INTO cursos (nome, descricao, tipo, carga_horaria, data_inicio, data_fim, instrutores, status, tem_avaliacao, nota_minima, imagem, conteudo_programatico)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?)",
            [$d['nome'], $d['descricao'], $d['tipo'], $d['carga_horaria'],
             $d['data_inicio'] ?: null, $d['data_fim'] ?: null,
             $d['instrutores'] ?? null,
             $d['status'] ?? 1, $d['tem_avaliacao'] ?? 0, $d['nota_minima'] ?? 60,
             $d['imagem'] ?? null, $d['conteudo_programatico'] ?? null]
        );
        return (int)$this->lastId();
    }

    public function atualizar(int $id, array $d): bool {
        return $this->execute(
            "UPDATE cursos SET nome=?, descricao=?, tipo=?, carga_horaria=?, data_inicio=?, data_fim=?,
             instrutores=?, status=?, tem_avaliacao=?, nota_minima=?, conteudo_programatico=?" .
            (!empty($d['imagem']) ? ", imagem=?" : "") . " WHERE id=?",
            !empty($d['imagem'])
                ? [$d['nome'], $d['descricao'], $d['tipo'], $d['carga_horaria'],
                   $d['data_inicio'] ?: null, $d['data_fim'] ?: null,
                   $d['instrutores'] ?? null,
                   $d['status'], $d['tem_avaliacao'] ?? 0, $d['nota_minima'] ?? 60,
                   $d['conteudo_programatico'] ?? null, $d['imagem'], $id]
                : [$d['nome'], $d['descricao'], $d['tipo'], $d['carga_horaria'],
                   $d['data_inicio'] ?: null, $d['data_fim'] ?: null,
                   $d['instrutores'] ?? null,
                   $d['status'], $d['tem_avaliacao'] ?? 0, $d['nota_minima'] ?? 60,
                   $d['conteudo_programatico'] ?? null, $id]
        );
    }

    public function deletar(int $id): bool {
        return $this->execute("DELETE FROM cursos WHERE id=?", [$id]);
    }

    public function cursosAtivos(): array {
        return $this->findAll("SELECT * FROM cursos WHERE status=1 ORDER BY nome");
    }

    public function cursosDoAluno(int $alunoId): array {
        return $this->findAll(
            "SELECT c.*, m.status as status_matricula, m.progresso, m.id as matricula_id
             FROM cursos c JOIN matriculas m ON c.id=m.curso_id
             WHERE m.aluno_id=? AND m.status != 'cancelada' ORDER BY c.nome",
            [$alunoId]
        );
    }
}