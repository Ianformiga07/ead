<?php
/**
 * app/models/CertificadoModel.php — CRMV EAD
 * Modelo de certificados — suporta verso_conteudo (CKEditor) e texto_frente
 */

class CertificadoModel extends Model {

    /** Busca certificado com dados do aluno e curso */
    public function buscar(int $alunoId, int $cursoId): array|false {
        return $this->find(
            "SELECT cert.*,
                    u.nome as aluno_nome, u.cpf as aluno_cpf,
                    c.nome as curso_nome, c.carga_horaria, c.tipo as curso_tipo
             FROM certificados cert
             JOIN usuarios u ON cert.aluno_id = u.id
             JOIN cursos c ON cert.curso_id = c.id
             WHERE cert.aluno_id = ? AND cert.curso_id = ?",
            [$alunoId, $cursoId]
        );
    }

    /** Busca certificado por código de validação */
    public function buscarPorCodigo(string $codigo): array|false {
        return $this->find(
            "SELECT cert.*,
                    u.nome as aluno_nome, u.cpf as aluno_cpf,
                    c.nome as curso_nome, c.carga_horaria
             FROM certificados cert
             JOIN usuarios u ON cert.aluno_id = u.id
             JOIN cursos c ON cert.curso_id = c.id
             WHERE cert.codigo = ?",
            [$codigo]
        );
    }

    /** Cria ou atualiza certificado */
    public function criar(int $alunoId, int $cursoId, string $codigo, string $arquivo = ''): int {
        $this->execute(
            "INSERT INTO certificados (aluno_id, curso_id, codigo, arquivo)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE codigo = ?, arquivo = ?",
            [$alunoId, $cursoId, $codigo, $arquivo, $codigo, $arquivo]
        );
        return (int)$this->lastId();
    }

    /** Busca modelo de certificado do curso (com campos novos) */
    public function modelo(int $cursoId): array|false {
        return $this->find(
            "SELECT * FROM modelos_certificado WHERE curso_id = ?",
            [$cursoId]
        );
    }

    /**
     * Salva ou atualiza o modelo de certificado
     * $d pode conter: frente, verso (imagens), verso_conteudo (HTML CKEditor), texto_frente
     */
    public function salvarModelo(int $cursoId, array $d): void {
        $exists = $this->find("SELECT id FROM modelos_certificado WHERE curso_id = ?", [$cursoId]);

        if ($exists) {
            $sets   = [];
            $params = [];

            // Imagens (upload)
            if (!empty($d['frente']))         { $sets[] = "frente=?";         $params[] = $d['frente']; }
            if (!empty($d['verso']))          { $sets[] = "verso=?";          $params[] = $d['verso']; }
            // Conteúdo textual
            if (array_key_exists('verso_conteudo', $d)) { $sets[] = "verso_conteudo=?"; $params[] = $d['verso_conteudo']; }
            if (array_key_exists('texto_frente', $d))   { $sets[] = "texto_frente=?";   $params[] = $d['texto_frente']; }

            if ($sets) {
                $params[] = $cursoId;
                $this->execute(
                    "UPDATE modelos_certificado SET " . implode(', ', $sets) . " WHERE curso_id = ?",
                    $params
                );
            }
        } else {
            $this->execute(
                "INSERT INTO modelos_certificado (curso_id, frente, verso, verso_conteudo, texto_frente)
                 VALUES (?, ?, ?, ?, ?)",
                [
                    $cursoId,
                    $d['frente']         ?? null,
                    $d['verso']          ?? null,
                    $d['verso_conteudo'] ?? null,
                    $d['texto_frente']   ?? null,
                ]
            );
        }
    }

    /** Lista certificados emitidos para um curso */
    public function doCurso(int $cursoId): array {
        return $this->findAll(
            "SELECT cert.*, u.nome as aluno_nome
             FROM certificados cert
             JOIN usuarios u ON cert.aluno_id = u.id
             WHERE cert.curso_id = ?
             ORDER BY cert.emitido_em DESC",
            [$cursoId]
        );
    }
}
