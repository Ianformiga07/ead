<?php
// app/models/CertificadoModel.php

class CertificadoModel extends Model {
    public function buscar(int $alunoId, int $cursoId): array|false {
        return $this->find(
            "SELECT cert.*, u.nome as aluno_nome, c.nome as curso_nome,
                    c.carga_horaria, c.instrutores, c.conteudo_programatico
             FROM certificados cert
             JOIN usuarios u ON cert.aluno_id = u.id
             JOIN cursos c ON cert.curso_id = c.id
             WHERE cert.aluno_id=? AND cert.curso_id=?",
            [$alunoId, $cursoId]
        );
    }

    public function buscarPorCodigo(string $codigo): array|false {
        return $this->find(
            "SELECT cert.*, u.nome as aluno_nome, c.nome as curso_nome,
                    c.carga_horaria, c.instrutores
             FROM certificados cert
             JOIN usuarios u ON cert.aluno_id = u.id
             JOIN cursos c ON cert.curso_id = c.id
             WHERE cert.codigo=?",
            [$codigo]
        );
    }

    public function criar(int $alunoId, int $cursoId, string $codigo, string $arquivo = ''): int {
        $this->execute(
            "INSERT INTO certificados (aluno_id, curso_id, codigo, arquivo)
             VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE codigo=?, arquivo=?",
            [$alunoId, $cursoId, $codigo, $arquivo, $codigo, $arquivo]
        );
        return (int)$this->lastId();
    }

    public function modelo(int $cursoId): array|false {
        return $this->find("SELECT * FROM modelos_certificado WHERE curso_id=?", [$cursoId]);
    }

    public function salvarModelo(int $cursoId, array $d): void {
        $exists = $this->find("SELECT id FROM modelos_certificado WHERE curso_id=?", [$cursoId]);
        if ($exists) {
            $sets = [];
            $p    = [];
            if (!empty($d['frente'])) { $sets[] = "frente=?"; $p[] = $d['frente']; }
            if (!empty($d['verso']))  { $sets[] = "verso=?";  $p[] = $d['verso']; }
            if ($sets) {
                $p[] = $cursoId;
                $this->execute("UPDATE modelos_certificado SET " . implode(',', $sets) . " WHERE curso_id=?", $p);
            }
        } else {
            $this->execute(
                "INSERT INTO modelos_certificado (curso_id, frente, verso) VALUES (?,?,?)",
                [$cursoId, $d['frente'] ?? null, $d['verso'] ?? null]
            );
        }
    }

    public function doCurso(int $cursoId): array {
        return $this->findAll(
            "SELECT cert.*, u.nome as aluno_nome FROM certificados cert
             JOIN usuarios u ON cert.aluno_id=u.id WHERE cert.curso_id=?",
            [$cursoId]
        );
    }
}
