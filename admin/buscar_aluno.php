<?php
/**
 * admin/buscar_aluno.php
 * Endpoint AJAX — Busca alunos para autocomplete na matrícula
 * Retorna JSON com id, nome, cpf, email
 * CRMV EAD
 */
require_once __DIR__ . '/../app/bootstrap.php';
authCheck('admin');

// Resposta sempre em JSON
header('Content-Type: application/json; charset=UTF-8');

// Aceita apenas GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido']);
    exit;
}

$q        = trim($_GET['q'] ?? '');
$excluirCurso = (int)($_GET['excluir_curso'] ?? 0); // exclui já matriculados

// Mínimo 2 caracteres para disparar a busca
if (mb_strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

try {
    $db   = getDB();
    $like = '%' . $q . '%';

    // Base da query: busca por nome, CPF ou e-mail
    $sql = "SELECT id, nome, email, cpf, telefone
            FROM usuarios
            WHERE perfil = 'aluno'
              AND status = 1
              AND (nome LIKE ? OR cpf LIKE ? OR email LIKE ?)";
    $params = [$like, $like, $like];

    // Se for contexto de um curso específico, excluir já matriculados ativos
    if ($excluirCurso > 0) {
        $sql .= " AND id NOT IN (
                    SELECT aluno_id FROM matriculas
                    WHERE curso_id = ? AND status != 'cancelada'
                  )";
        $params[] = $excluirCurso;
    }

    $sql .= " ORDER BY nome LIMIT 15";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $alunos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Formatar resultado — nunca expor senha ou dados sensíveis
    $resultado = array_map(function ($a) {
        return [
            'id'    => (int)$a['id'],
            'nome'  => $a['nome'],
            'email' => $a['email'],
            'cpf'   => $a['cpf']   ?: null,
            'info'  => implode(' · ', array_filter([
                $a['cpf'] ? 'CPF: ' . $a['cpf'] : null,
                $a['email'],
            ])),
        ];
    }, $alunos);

    echo json_encode($resultado, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno']);
}
exit;
