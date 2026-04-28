<?php
// app/models/AvaliacaoModel.php

class AvaliacaoModel extends Model
{
    public function porCurso(int $cursoId): array|false
    {
        return $this->find('SELECT * FROM avaliacoes WHERE curso_id=?', [$cursoId]);
    }

    public function findById(int $id): array|false
    {
        return $this->find('SELECT * FROM avaliacoes WHERE id=?', [$id]);
    }

    public function criar(array $d): int
    {
        $this->execute(
            'INSERT INTO avaliacoes (curso_id, titulo, descricao, tentativas) VALUES (?,?,?,?)',
            [$d['curso_id'], $d['titulo'], $d['descricao'] ?? null, $d['tentativas'] ?? 1]
        );
        return (int)$this->lastId();
    }

    public function atualizar(int $id, array $d): bool
    {
        return $this->execute(
            'UPDATE avaliacoes SET titulo=?, descricao=?, tentativas=? WHERE id=?',
            [$d['titulo'], $d['descricao'] ?? null, $d['tentativas'] ?? 1, $id]
        );
    }

    public function deletar(int $id): bool
    {
        return $this->execute('DELETE FROM avaliacoes WHERE id=?', [$id]);
    }

    // ── Perguntas ─────────────────────────────────────────────

    public function perguntas(int $avaliacaoId): array
    {
        return $this->findAll(
            'SELECT * FROM perguntas WHERE avaliacao_id=? ORDER BY ordem',
            [$avaliacaoId]
        );
    }

    public function perguntaById(int $id): array|false
    {
        return $this->find('SELECT * FROM perguntas WHERE id=?', [$id]);
    }

    public function criarPergunta(array $d): int
    {
        $this->execute(
            'INSERT INTO perguntas (avaliacao_id, enunciado, pontos, ordem) VALUES (?,?,?,?)',
            [$d['avaliacao_id'], $d['enunciado'], $d['pontos'] ?? 1, $d['ordem'] ?? 1]
        );
        return (int)$this->lastId();
    }

    public function deletarPergunta(int $id): bool
    {
        return $this->execute('DELETE FROM perguntas WHERE id=?', [$id]);
    }

    // ── Alternativas ──────────────────────────────────────────

    public function alternativas(int $perguntaId): array
    {
        return $this->findAll('SELECT * FROM alternativas WHERE pergunta_id=?', [$perguntaId]);
    }

    public function findAlternativa(int $id): array|false
    {
        return $this->find('SELECT * FROM alternativas WHERE id=?', [$id]);
    }

    public function criarAlternativa(int $perguntaId, string $texto, bool $correta): void
    {
        $this->execute(
            'INSERT INTO alternativas (pergunta_id, texto, correta) VALUES (?,?,?)',
            [$perguntaId, $texto, (int)$correta]
        );
    }

    public function deletarAlternativas(int $perguntaId): bool
    {
        return $this->execute('DELETE FROM alternativas WHERE pergunta_id=?', [$perguntaId]);
    }

    // ── Tentativas ────────────────────────────────────────────

    public function tentativasAluno(int $alunoId, int $avaliacaoId): int
    {
        return $this->count(
            'SELECT COUNT(*) FROM tentativas_avaliacao WHERE aluno_id=? AND avaliacao_id=?',
            [$alunoId, $avaliacaoId]
        );
    }

    public function ultimaTentativa(int $alunoId, int $avaliacaoId): array|false
    {
        return $this->find(
            'SELECT * FROM tentativas_avaliacao WHERE aluno_id=? AND avaliacao_id=?
             ORDER BY realizado_em DESC LIMIT 1',
            [$alunoId, $avaliacaoId]
        );
    }

    public function registrarTentativa(int $alunoId, int $avaliacaoId, float $nota, bool $aprovado): int
    {
        $this->execute(
            'INSERT INTO tentativas_avaliacao (aluno_id, avaliacao_id, nota, aprovado) VALUES (?,?,?,?)',
            [$alunoId, $avaliacaoId, $nota, (int)$aprovado]
        );
        return (int)$this->lastId();
    }

    public function registrarResposta(int $tentId, int $perguntaId, int $altId, bool $correta): void
    {
        $this->execute(
            'INSERT INTO respostas_aluno (tentativa_id, pergunta_id, alternativa_id, correta) VALUES (?,?,?,?)',
            [$tentId, $perguntaId, $altId, (int)$correta]
        );
    }
}
