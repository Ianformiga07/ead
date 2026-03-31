<?php
// app/models/Model.php

abstract class Model {
    protected PDO $db;
    protected string $table = '';

    public function __construct() {
        $this->db = getDB();
    }

    protected function find(string $sql, array $params = []): array|false {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }

    protected function findAll(string $sql, array $params = []): array {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    protected function execute(string $sql, array $params = []): bool {
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    protected function lastId(): string {
        return $this->db->lastInsertId();
    }

    protected function count(string $sql, array $params = []): int {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }
}
