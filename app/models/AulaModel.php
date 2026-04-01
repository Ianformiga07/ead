<?php
// app/models/AulaModel.php

class AulaModel extends Model {
    public function porCurso(int $cursoId): array {
        return $this->findAll("SELECT * FROM aulas WHERE curso_id=? AND status=1 ORDER BY ordem", [$cursoId]);
    }

    public function findById(int $id): array|false {
        return $this->find("SELECT * FROM aulas WHERE id=?", [$id]);
    }

    public function criar(array $d): int {
        $this->execute(
            "INSERT INTO aulas (curso_id, titulo, descricao, url_video, ordem, status) VALUES (?,?,?,?,?,?)",
            [$d['curso_id'], $d['titulo'], $d['descricao'] ?? null, $d['url_video'] ?? null, $d['ordem'] ?? 1, $d['status'] ?? 1]
        );
        return (int)$this->lastId();
    }

    public function atualizar(int $id, array $d): bool {
        return $this->execute(
            "UPDATE aulas SET titulo=?, descricao=?, url_video=?, ordem=?, status=? WHERE id=?",
            [$d['titulo'], $d['descricao'] ?? null, $d['url_video'] ?? null, $d['ordem'] ?? 1, $d['status'] ?? 1, $id]
        );
    }

    public function deletar(int $id): bool {
        return $this->execute("DELETE FROM aulas WHERE id=?", [$id]);
    }

    public function marcarAssistida(int $alunoId, int $aulaId): void {
        $this->execute(
            "INSERT INTO progresso_aulas (aluno_id, aula_id, assistido, assistido_em) VALUES (?,?,1,NOW())
             ON DUPLICATE KEY UPDATE assistido=1, assistido_em=NOW()",
            [$alunoId, $aulaId]
        );
    }

    public function assistidas(int $alunoId, int $cursoId): array {
        $rows = $this->findAll(
            "SELECT p.aula_id FROM progresso_aulas p
             JOIN aulas a ON p.aula_id=a.id
             WHERE p.aluno_id=? AND a.curso_id=? AND a.status=1 AND p.assistido=1",
            [$alunoId, $cursoId]
        );
        return array_column($rows, 'aula_id');
    }

    public function totalPorCurso(int $cursoId): int {
        return $this->count("SELECT COUNT(*) FROM aulas WHERE curso_id=? AND status=1", [$cursoId]);
    }
}