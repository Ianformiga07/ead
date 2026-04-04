<?php
/**
 * app/models/CertificadoModel.php — CRMV EAD
 * Modelo de certificados — suporta frente, verso, conteúdo programático e instrutores
 */

class CertificadoModel extends Model {

    /** Busca certificado com dados do aluno e curso */
    public function buscar(int $alunoId, int $cursoId): array|false {
        return $this->find(
            "SELECT cert.*,
                    u.nome as aluno_nome, u.cpf as aluno_cpf, u.crmv as aluno_crmv,
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
                    u.nome as aluno_nome, u.cpf as aluno_cpf, u.crmv as aluno_crmv,
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

    /** Busca modelo de certificado do curso */
    public function modelo(int $cursoId): array|false {
        return $this->find(
            "SELECT * FROM modelos_certificado WHERE curso_id = ?",
            [$cursoId]
        );
    }

    /**
     * Salva ou atualiza o modelo de certificado
     * Campos suportados: frente, verso (imagens), texto_frente, verso_conteudo,
     *                    nome_cert, instrutor, conteudo_prog, ativar_verso
     */
    public function salvarModelo(int $cursoId, array $d): void {
        $exists = $this->find("SELECT id FROM modelos_certificado WHERE curso_id = ?", [$cursoId]);

        $campos = ['frente', 'verso', 'texto_frente', 'verso_conteudo',
                   'nome_cert', 'instrutor', 'conteudo_prog', 'ativar_verso'];

        if ($exists) {
            $sets   = [];
            $params = [];

            foreach ($campos as $campo) {
                // Imagens só atualizam se vier novo upload
                if (in_array($campo, ['frente', 'verso'])) {
                    if (!empty($d[$campo])) {
                        $sets[]   = "$campo=?";
                        $params[] = $d[$campo];
                    }
                } else {
                    // Campos texto sempre atualizam (mesmo que vazio)
                    if (array_key_exists($campo, $d)) {
                        $sets[]   = "$campo=?";
                        $params[] = $d[$campo];
                    }
                }
            }

            if ($sets) {
                $params[] = $cursoId;
                $this->execute(
                    "UPDATE modelos_certificado SET " . implode(', ', $sets) . " WHERE curso_id = ?",
                    $params
                );
            }
        } else {
            $this->execute(
                "INSERT INTO modelos_certificado
                 (curso_id, frente, verso, texto_frente, verso_conteudo,
                  nome_cert, instrutor, conteudo_prog, ativar_verso)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $cursoId,
                    $d['frente']         ?? null,
                    $d['verso']          ?? null,
                    $d['texto_frente']   ?? null,
                    $d['verso_conteudo'] ?? null,
                    $d['nome_cert']      ?? null,
                    $d['instrutor']      ?? null,
                    $d['conteudo_prog']  ?? null,
                    $d['ativar_verso']   ?? 0,
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
