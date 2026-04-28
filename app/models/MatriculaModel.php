<?php
// app/models/MatriculaModel.php

class MatriculaModel extends Model
{
    public function matricular(int $alunoId, int $cursoId): bool
    {
        return $this->execute(
            'INSERT IGNORE INTO matriculas (aluno_id, curso_id) VALUES (?,?)',
            [$alunoId, $cursoId]
        );
    }

    public function cancelar(int $id): bool
    {
        return $this->execute("UPDATE matriculas SET status='cancelada' WHERE id=?", [$id]);
    }

    public function concluir(int $alunoId, int $cursoId): bool
    {
        return $this->execute(
            "UPDATE matriculas SET status='concluida', progresso=100, concluido_em=NOW()
             WHERE aluno_id=? AND curso_id=?",
            [$alunoId, $cursoId]
        );
    }

    public function atualizarProgresso(int $alunoId, int $cursoId, int $prog): bool
    {
        return $this->execute(
            'UPDATE matriculas SET progresso=? WHERE aluno_id=? AND curso_id=?',
            [$prog, $alunoId, $cursoId]
        );
    }

    public function buscar(int $alunoId, int $cursoId): array|false
    {
        return $this->find(
            'SELECT * FROM matriculas WHERE aluno_id=? AND curso_id=?',
            [$alunoId, $cursoId]
        );
    }

    public function cursosDoAluno(int $alunoId): array
    {
        return $this->findAll(
            "SELECT c.*, m.status as status_matricula, m.progresso, m.matriculado_em, m.id as matricula_id
             FROM cursos c
             JOIN matriculas m ON c.id=m.curso_id
             WHERE m.aluno_id=? AND m.status != 'cancelada'
             ORDER BY c.nome",
            [$alunoId]
        );
    }

    public function alunosDoCurso(int $cursoId): array
    {
        return $this->findAll(
            "SELECT u.*, m.status as status_matricula, m.progresso, m.matriculado_em, m.id as matricula_id
             FROM usuarios u
             JOIN matriculas m ON u.id=m.aluno_id
             WHERE m.curso_id=? AND m.status != 'cancelada'
             ORDER BY u.nome",
            [$cursoId]
        );
    }

    public function cursosNaoMatriculado(int $alunoId): array
    {
        return $this->findAll(
            "SELECT * FROM cursos WHERE status=1
             AND id NOT IN (SELECT curso_id FROM matriculas WHERE aluno_id=? AND status != 'cancelada')
             ORDER BY nome",
            [$alunoId]
        );
    }
}
