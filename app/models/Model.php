<?php
// app/models/Model.php

abstract class Model {
    protected PDO $db;
    protected string $table = '';

    public function __construct() {
        $this->db = getDB();
    }

    /**
     * Garante que TODA query passe por prepared statement.
     * Lança exceção se tentarem passar SQL com interpolação direta de variáveis.
     */
    private function assertPrepared(string $sql): void {
        // Detecta padrões suspeitos: concatenação direta de variável na SQL
        // Ex.: "... WHERE id = $id" ou "... WHERE nome = '" . $var
        if (preg_match('/\$[a-zA-Z_]|\.\s*["\']/', $sql)) {
            throw new \InvalidArgumentException('SQL não pode conter variáveis interpoladas diretamente. Use prepared statements (?).');
        }
    }

    protected function find(string $sql, array $params = []): array|false {
        $this->assertPrepared($sql);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }

    protected function findAll(string $sql, array $params = []): array {
        $this->assertPrepared($sql);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    protected function execute(string $sql, array $params = []): bool {
        $this->assertPrepared($sql);
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    protected function lastId(): string {
        return $this->db->lastInsertId();
    }

    protected function count(string $sql, array $params = []): int {
        $this->assertPrepared($sql);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }
}
