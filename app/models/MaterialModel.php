<?php
// app/models/MaterialModel.php

class MaterialModel extends Model
{
    public function porCurso(int $cursoId): array
    {
        return $this->findAll(
            'SELECT * FROM materiais WHERE curso_id=? ORDER BY titulo',
            [$cursoId]
        );
    }

    public function findById(int $id): array|false
    {
        return $this->find('SELECT * FROM materiais WHERE id=?', [$id]);
    }

    public function criar(array $d): int
    {
        $this->execute(
            'INSERT INTO materiais (curso_id, titulo, arquivo, tipo, tamanho) VALUES (?,?,?,?,?)',
            [$d['curso_id'], $d['titulo'], $d['arquivo'], $d['tipo'] ?? null, $d['tamanho'] ?? 0]
        );
        return (int)$this->lastId();
    }

    public function deletar(int $id): bool
    {
        return $this->execute('DELETE FROM materiais WHERE id=?', [$id]);
    }
}
