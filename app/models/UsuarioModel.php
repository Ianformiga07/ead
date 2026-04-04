<?php
// app/models/UsuarioModel.php

class UsuarioModel extends Model {
    protected string $table = 'usuarios';

    public function findByEmail(string $email): array|false {
        return $this->find("SELECT * FROM usuarios WHERE email = ? AND status = 1", [$email]);
    }

    public function findById(int $id): array|false {
        return $this->find("SELECT * FROM usuarios WHERE id = ?", [$id]);
    }

    /* ── Listar alunos ── */
    public function listar(int $offset = 0, int $limit = 20, string $busca = ''): array {
        $like = "%$busca%";
        return $this->findAll(
            "SELECT * FROM usuarios WHERE perfil='aluno'
             AND (nome LIKE ? OR email LIKE ? OR cpf LIKE ? OR crmv LIKE ?)
             ORDER BY nome LIMIT ? OFFSET ?",
            [$like, $like, $like, $like, $limit, $offset]
        );
    }

    public function totalAlunos(string $busca = ''): int {
        $like = "%$busca%";
        return $this->count(
            "SELECT COUNT(*) FROM usuarios WHERE perfil='aluno'
             AND (nome LIKE ? OR email LIKE ? OR cpf LIKE ? OR crmv LIKE ?)",
            [$like, $like, $like, $like]
        );
    }

    /* ── Listar operadores/admins ── */
    public function listarUsuarios(int $offset = 0, int $limit = 20, string $busca = ''): array {
        $like = "%$busca%";
        return $this->findAll(
            "SELECT * FROM usuarios WHERE perfil IN ('admin','operador')
             AND (nome LIKE ? OR email LIKE ?)
             ORDER BY perfil, nome LIMIT ? OFFSET ?",
            [$like, $like, $limit, $offset]
        );
    }

    public function totalUsuarios(string $busca = ''): int {
        $like = "%$busca%";
        return $this->count(
            "SELECT COUNT(*) FROM usuarios WHERE perfil IN ('admin','operador')
             AND (nome LIKE ? OR email LIKE ?)",
            [$like, $like]
        );
    }

    /* ── Criar usuario ── */
    public function criar(array $dados): int {
        $this->execute(
            "INSERT INTO usuarios
             (nome, email, senha, cpf, telefone, perfil, status,
              crmv, data_nascimento, sexo, cep, logradouro, numero,
              complemento, bairro, cidade, estado, especialidade)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
            [
                $dados['nome'],
                $dados['email'],
                password_hash($dados['senha'], PASSWORD_BCRYPT, ['cost' => 12]),
                $dados['cpf']             ?? null,
                $dados['telefone']        ?? null,
                $dados['perfil']          ?? 'aluno',
                $dados['status']          ?? 1,
                $dados['crmv']            ?? null,
                $dados['data_nascimento'] ?? null,
                $dados['sexo']            ?? null,
                $dados['cep']             ?? null,
                $dados['logradouro']      ?? null,
                $dados['numero']          ?? null,
                $dados['complemento']     ?? null,
                $dados['bairro']          ?? null,
                $dados['cidade']          ?? null,
                $dados['estado']          ?? null,
                $dados['especialidade']   ?? null,
            ]
        );
        return (int)$this->lastId();
    }

    /* ── Atualizar usuario ── */
    public function atualizar(int $id, array $dados): bool {
        $sets   = "nome=?, email=?, cpf=?, telefone=?, status=?,
                   crmv=?, data_nascimento=?, sexo=?, cep=?,
                   logradouro=?, numero=?, complemento=?,
                   bairro=?, cidade=?, estado=?, especialidade=?";
        $params = [
            $dados['nome'],
            $dados['email'],
            $dados['cpf']             ?? null,
            $dados['telefone']        ?? null,
            $dados['status'],
            $dados['crmv']            ?? null,
            $dados['data_nascimento'] ?? null,
            $dados['sexo']            ?? null,
            $dados['cep']             ?? null,
            $dados['logradouro']      ?? null,
            $dados['numero']          ?? null,
            $dados['complemento']     ?? null,
            $dados['bairro']          ?? null,
            $dados['cidade']          ?? null,
            $dados['estado']          ?? null,
            $dados['especialidade']   ?? null,
        ];

        if (isset($dados['perfil'])) {
            $sets    .= ", perfil=?";
            $params[] = $dados['perfil'];
        }

        if (!empty($dados['senha'])) {
            $sets    .= ", senha=?";
            $params[] = password_hash($dados['senha'], PASSWORD_BCRYPT, ['cost' => 12]);
        }

        $params[] = $id;
        return $this->execute("UPDATE usuarios SET $sets WHERE id=?", $params);
    }

    public function deletar(int $id): bool {
        return $this->execute("UPDATE usuarios SET status=0 WHERE id=?", [$id]);
    }

    public function emailExiste(string $email, int $excludeId = 0): bool {
        return $this->count(
            "SELECT COUNT(*) FROM usuarios WHERE email=? AND id != ?",
            [$email, $excludeId]
        ) > 0;
    }

    public function cpfExiste(string $cpf, int $excludeId = 0): bool {
        return $this->count(
            "SELECT COUNT(*) FROM usuarios WHERE cpf=? AND id != ?",
            [$cpf, $excludeId]
        ) > 0;
    }
}
