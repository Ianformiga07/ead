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

// ─────────────────────────────────────────────────────────────
// app/models/AvaliacaoModel.php  — com controle de tentativas extras
// ─────────────────────────────────────────────────────────────
// ALTERAÇÕES em relação ao original:
//   • tentativasAluno()    → conta apenas tentativas VÁLIDAS (não invalidadas)
//   • ultimaTentativa()    → retorna apenas tentativas válidas
//   • podeRealizar()       → nova: encapsula toda a lógica de permissão
//   • tentativasExtras()   → nova: quantas extras disponíveis para o aluno
//   • concederTentativaExtra() → nova: admin libera nova tentativa
//   • invalidarTentativa() → nova: admin invalida tentativa específica
//   • historicoTentativas() → nova: todas as tentativas (válidas + invalidadas)
//   • marcarExtraUtilizada() → interna: chamada ao registrar nova tentativa
// ─────────────────────────────────────────────────────────────

class AvaliacaoModel extends Model
{
    // ── Avaliações ────────────────────────────────────────────

    public function porCurso(int $cursoId): array|false
    {
        return $this->find("SELECT * FROM avaliacoes WHERE curso_id=?", [$cursoId]);
    }

    public function findById(int $id): array|false
    {
        return $this->find("SELECT * FROM avaliacoes WHERE id=?", [$id]);
    }

    public function criar(array $d): int
    {
        $this->execute(
            "INSERT INTO avaliacoes (curso_id, titulo, descricao, tentativas) VALUES (?,?,?,?)",
            [$d['curso_id'], $d['titulo'], $d['descricao'] ?? null, $d['tentativas'] ?? 1]
        );
        return (int)$this->lastId();
    }

    public function atualizar(int $id, array $d): bool
    {
        return $this->execute(
            "UPDATE avaliacoes SET titulo=?, descricao=?, tentativas=? WHERE id=?",
            [$d['titulo'], $d['descricao'] ?? null, $d['tentativas'] ?? 1, $id]
        );
    }

    public function deletar(int $id): bool
    {
        return $this->execute("DELETE FROM avaliacoes WHERE id=?", [$id]);
    }

    // ── Perguntas ─────────────────────────────────────────────

    public function perguntas(int $avaliacaoId): array
    {
        return $this->findAll(
            "SELECT * FROM perguntas WHERE avaliacao_id=? ORDER BY ordem",
            [$avaliacaoId]
        );
    }

    public function perguntaById(int $id): array|false
    {
        return $this->find("SELECT * FROM perguntas WHERE id=?", [$id]);
    }

    public function criarPergunta(array $d): int
    {
        $this->execute(
            "INSERT INTO perguntas (avaliacao_id, enunciado, pontos, ordem) VALUES (?,?,?,?)",
            [$d['avaliacao_id'], $d['enunciado'], $d['pontos'] ?? 1, $d['ordem'] ?? 1]
        );
        return (int)$this->lastId();
    }

    public function deletarPergunta(int $id): bool
    {
        return $this->execute("DELETE FROM perguntas WHERE id=?", [$id]);
    }

    // ── Alternativas ──────────────────────────────────────────

    public function alternativas(int $perguntaId): array
    {
        return $this->findAll(
            "SELECT * FROM alternativas WHERE pergunta_id=?",
            [$perguntaId]
        );
    }

    public function criarAlternativa(int $perguntaId, string $texto, bool $correta): void
    {
        $this->execute(
            "INSERT INTO alternativas (pergunta_id, texto, correta) VALUES (?,?,?)",
            [$perguntaId, $texto, (int)$correta]
        );
    }

    public function deletarAlternativas(int $perguntaId): bool
    {
        return $this->execute("DELETE FROM alternativas WHERE pergunta_id=?", [$perguntaId]);
    }

    // ── Tentativas ────────────────────────────────────────────

    /**
     * Conta apenas tentativas VÁLIDAS (não invalidadas pelo admin).
     * Mantém compatibilidade com o uso original.
     */
    public function tentativasAluno(int $alunoId, int $avaliacaoId): int
    {
        return $this->count(
            "SELECT COUNT(*) FROM tentativas_avaliacao
             WHERE aluno_id=? AND avaliacao_id=? AND invalidada=0",
            [$alunoId, $avaliacaoId]
        );
    }

    /**
     * Retorna a última tentativa VÁLIDA do aluno.
     */
    public function ultimaTentativa(int $alunoId, int $avaliacaoId): array|false
    {
        return $this->find(
            "SELECT * FROM tentativas_avaliacao
             WHERE aluno_id=? AND avaliacao_id=? AND invalidada=0
             ORDER BY realizado_em DESC LIMIT 1",
            [$alunoId, $avaliacaoId]
        );
    }

    /**
     * Verifica se o aluno pode realizar a avaliação.
     *
     * Regra: pode se (tentativas válidas < limite da avaliação)
     *        OU  se tem tentativa extra disponível (não utilizada).
     */
    public function podeRealizar(int $alunoId, int $avaliacaoId, int $limiteAvaliacao): bool
    {
        $tentativasUsadas = $this->tentativasAluno($alunoId, $avaliacaoId);

        if ($tentativasUsadas < $limiteAvaliacao) {
            return true;
        }

        return $this->tentativasExtrasDisponiveis($alunoId, $avaliacaoId) > 0;
    }

    /**
     * Quantas tentativas extras (não utilizadas) o aluno tem.
     */
    public function tentativasExtrasDisponiveis(int $alunoId, int $avaliacaoId): int
    {
        return $this->count(
            "SELECT COUNT(*) FROM tentativas_extras
             WHERE aluno_id=? AND avaliacao_id=? AND utilizada=0",
            [$alunoId, $avaliacaoId]
        );
    }

    /**
     * Registra uma nova tentativa.
     * Se o aluno usou tentativa extra, marca a mais antiga como utilizada.
     */
    public function registrarTentativa(
        int $alunoId,
        int $avaliacaoId,
        float $nota,
        bool $aprovado
    ): int {
        $this->execute(
            "INSERT INTO tentativas_avaliacao (aluno_id, avaliacao_id, nota, aprovado)
             VALUES (?,?,?,?)",
            [$alunoId, $avaliacaoId, $nota, (int)$aprovado]
        );
        $tentativaId = (int)$this->lastId();

        // Se estava usando uma tentativa extra, marcar como utilizada
        $this->marcarExtraUtilizadaSeNecessario($alunoId, $avaliacaoId);

        return $tentativaId;
    }

    public function registrarResposta(
        int $tentativaId,
        int $perguntaId,
        int $alternativaId,
        bool $correta
    ): void {
        $this->execute(
            "INSERT INTO respostas_aluno (tentativa_id, pergunta_id, alternativa_id, correta)
             VALUES (?,?,?,?)",
            [$tentativaId, $perguntaId, $alternativaId, (int)$correta]
        );
    }

    // ── Controle Admin: Tentativas Extras ─────────────────────

    /**
     * Admin concede uma nova tentativa para o aluno.
     *
     * @param int    $adminId     ID do administrador que está liberando
     * @param string $observacao  Motivo/anotação (opcional)
     */
    public function concederTentativaExtra(
        int $alunoId,
        int $avaliacaoId,
        int $adminId,
        string $observacao = ''
    ): bool {
        return $this->execute(
            "INSERT INTO tentativas_extras
                (aluno_id, avaliacao_id, concedida_por, observacao)
             VALUES (?,?,?,?)",
            [$alunoId, $avaliacaoId, $adminId, $observacao ?: null]
        );
    }

    /**
     * Admin invalida uma tentativa específica.
     * O histórico é preservado — a tentativa fica marcada como inválida.
     */
    public function invalidarTentativa(int $tentativaId, int $adminId, string $motivo = ''): bool
    {
        return $this->execute(
            "UPDATE tentativas_avaliacao
             SET invalidada=1, invalidada_por=?, invalidada_em=NOW(), motivo_invalidacao=?
             WHERE id=?",
            [$adminId, $motivo ?: null, $tentativaId]
        );
    }

    /**
     * Retorna o histórico COMPLETO de tentativas (válidas + invalidadas).
     * Usado no painel admin para auditoria.
     */
    public function historicoTentativas(int $alunoId, int $avaliacaoId): array
    {
        return $this->findAll(
            "SELECT ta.*,
                    u.nome AS invalidada_por_nome
             FROM tentativas_avaliacao ta
             LEFT JOIN usuarios u ON u.id = ta.invalidada_por
             WHERE ta.aluno_id=? AND ta.avaliacao_id=?
             ORDER BY ta.realizado_em DESC",
            [$alunoId, $avaliacaoId]
        );
    }

    /**
     * Lista todas as tentativas extras concedidas para um aluno numa avaliação.
     */
    public function listarExtras(int $alunoId, int $avaliacaoId): array
    {
        return $this->findAll(
            "SELECT te.*, u.nome AS concedida_por_nome
             FROM tentativas_extras te
             JOIN usuarios u ON u.id = te.concedida_por
             WHERE te.aluno_id=? AND te.avaliacao_id=?
             ORDER BY te.concedida_em DESC",
            [$alunoId, $avaliacaoId]
        );
    }

    // ── Internos ──────────────────────────────────────────────

    /**
     * Ao registrar tentativa, verifica se havia extra disponível e marca como usada.
     * Usa a extra mais antiga (FIFO), caso haja mais de uma.
     */
    private function marcarExtraUtilizadaSeNecessario(int $alunoId, int $avaliacaoId): void
    {
        // Busca a extra mais antiga ainda não utilizada
        $extra = $this->find(
            "SELECT id FROM tentativas_extras
             WHERE aluno_id=? AND avaliacao_id=? AND utilizada=0
             ORDER BY concedida_em ASC LIMIT 1",
            [$alunoId, $avaliacaoId]
        );

        if ($extra) {
            $this->execute(
                "UPDATE tentativas_extras SET utilizada=1, utilizada_em=NOW() WHERE id=?",
                [$extra['id']]
            );
        }
    }
}
