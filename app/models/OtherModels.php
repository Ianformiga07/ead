<?php
// app/models/MaterialModel.php
class MaterialModel extends Model {
    public function porCurso(int $cursoId): array {
        return $this->findAll("SELECT * FROM materiais WHERE curso_id=? ORDER BY titulo", [$cursoId]);
    }
    public function findById(int $id): array|false {
        return $this->find("SELECT * FROM materiais WHERE id=?", [$id]);
    }
    public function criar(array $d): int {
        $this->execute(
            "INSERT INTO materiais (curso_id, titulo, arquivo, tipo, tamanho) VALUES (?,?,?,?,?)",
            [$d['curso_id'], $d['titulo'], $d['arquivo'], $d['tipo'] ?? null, $d['tamanho'] ?? 0]
        );
        return (int)$this->lastId();
    }
    public function deletar(int $id): bool {
        return $this->execute("DELETE FROM materiais WHERE id=?", [$id]);
    }
}

// ─────────────────────────────────────────────────────────────
// app/models/MatriculaModel.php
class MatriculaModel extends Model {
    public function matricular(int $alunoId, int $cursoId): bool {
        return $this->execute(
            "INSERT IGNORE INTO matriculas (aluno_id, curso_id) VALUES (?,?)",
            [$alunoId, $cursoId]
        );
    }

    public function cancelar(int $id): bool {
        return $this->execute("UPDATE matriculas SET status='cancelada' WHERE id=?", [$id]);
    }

    public function concluir(int $alunoId, int $cursoId): bool {
        return $this->execute(
            "UPDATE matriculas SET status='concluida', progresso=100, concluido_em=NOW()
             WHERE aluno_id=? AND curso_id=?",
            [$alunoId, $cursoId]
        );
    }

    public function atualizarProgresso(int $alunoId, int $cursoId, int $prog): bool {
        return $this->execute(
            "UPDATE matriculas SET progresso=? WHERE aluno_id=? AND curso_id=?",
            [$prog, $alunoId, $cursoId]
        );
    }

    public function buscar(int $alunoId, int $cursoId): array|false {
        return $this->find(
            "SELECT * FROM matriculas WHERE aluno_id=? AND curso_id=?",
            [$alunoId, $cursoId]
        );
    }

    public function alunosDoCurso(int $cursoId): array {
        return $this->findAll(
            "SELECT u.*, m.status as status_matricula, m.progresso, m.matriculado_em, m.id as matricula_id
             FROM usuarios u JOIN matriculas m ON u.id=m.aluno_id
             WHERE m.curso_id=? AND m.status != 'cancelada' ORDER BY u.nome",
            [$cursoId]
        );
    }

    public function cursosNaoMatriculado(int $alunoId): array {
        return $this->findAll(
            "SELECT * FROM cursos WHERE status=1 AND id NOT IN
             (SELECT curso_id FROM matriculas WHERE aluno_id=? AND status != 'cancelada')
             ORDER BY nome",
            [$alunoId]
        );
    }
}

// ─────────────────────────────────────────────────────────────
// app/models/AvaliacaoModel.php
class AvaliacaoModel extends Model {
    public function porCurso(int $cursoId): array|false {
        return $this->find("SELECT * FROM avaliacoes WHERE curso_id=?", [$cursoId]);
    }

    public function findById(int $id): array|false {
        return $this->find("SELECT * FROM avaliacoes WHERE id=?", [$id]);
    }

    public function criar(array $d): int {
        $this->execute(
            "INSERT INTO avaliacoes (curso_id, titulo, descricao, tentativas) VALUES (?,?,?,?)",
            [$d['curso_id'], $d['titulo'], $d['descricao'] ?? null, $d['tentativas'] ?? 1]
        );
        return (int)$this->lastId();
    }

    public function atualizar(int $id, array $d): bool {
        return $this->execute(
            "UPDATE avaliacoes SET titulo=?, descricao=?, tentativas=? WHERE id=?",
            [$d['titulo'], $d['descricao'] ?? null, $d['tentativas'] ?? 1, $id]
        );
    }

    public function deletar(int $id): bool {
        return $this->execute("DELETE FROM avaliacoes WHERE id=?", [$id]);
    }

    /* PERGUNTAS */
    public function perguntas(int $avaliacaoId): array {
        return $this->findAll("SELECT * FROM perguntas WHERE avaliacao_id=? ORDER BY ordem", [$avaliacaoId]);
    }

    public function perguntaById(int $id): array|false {
        return $this->find("SELECT * FROM perguntas WHERE id=?", [$id]);
    }

    public function criarPergunta(array $d): int {
        $this->execute(
            "INSERT INTO perguntas (avaliacao_id, enunciado, pontos, ordem) VALUES (?,?,?,?)",
            [$d['avaliacao_id'], $d['enunciado'], $d['pontos'] ?? 1, $d['ordem'] ?? 1]
        );
        return (int)$this->lastId();
    }

    public function deletarPergunta(int $id): bool {
        return $this->execute("DELETE FROM perguntas WHERE id=?", [$id]);
    }

    /* ALTERNATIVAS */
    public function alternativas(int $perguntaId): array {
        return $this->findAll("SELECT * FROM alternativas WHERE pergunta_id=?", [$perguntaId]);
    }

    public function criarAlternativa(int $perguntaId, string $texto, bool $correta): void {
        $this->execute(
            "INSERT INTO alternativas (pergunta_id, texto, correta) VALUES (?,?,?)",
            [$perguntaId, $texto, (int)$correta]
        );
    }

    public function deletarAlternativas(int $perguntaId): bool {
        return $this->execute("DELETE FROM alternativas WHERE pergunta_id=?", [$perguntaId]);
    }

    /* TENTATIVAS */
    public function tentativasAluno(int $alunoId, int $avaliacaoId): int {
        return $this->count(
            "SELECT COUNT(*) FROM tentativas_avaliacao WHERE aluno_id=? AND avaliacao_id=?",
            [$alunoId, $avaliacaoId]
        );
    }

    public function ultimaTentativa(int $alunoId, int $avaliacaoId): array|false {
        return $this->find(
            "SELECT * FROM tentativas_avaliacao WHERE aluno_id=? AND avaliacao_id=?
             ORDER BY realizado_em DESC LIMIT 1",
            [$alunoId, $avaliacaoId]
        );
    }

    public function registrarTentativa(int $alunoId, int $avaliacaoId, float $nota, bool $aprovado): int {
        $this->execute(
            "INSERT INTO tentativas_avaliacao (aluno_id, avaliacao_id, nota, aprovado) VALUES (?,?,?,?)",
            [$alunoId, $avaliacaoId, $nota, (int)$aprovado]
        );
        return (int)$this->lastId();
    }

    public function registrarResposta(int $tentativaId, int $perguntaId, int $alternativaId, bool $correta): void {
        $this->execute(
            "INSERT INTO respostas_aluno (tentativa_id, pergunta_id, alternativa_id, correta) VALUES (?,?,?,?)",
            [$tentativaId, $perguntaId, $alternativaId, (int)$correta]
        );
    }
}
