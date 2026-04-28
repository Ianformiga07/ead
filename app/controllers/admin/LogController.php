<?php
/**
 * app/controllers/admin/LogController.php
 */

class LogController extends BaseController
{
    public function index(array $params = []): void
    {
        $this->authSoAdmin();

        $db    = getDB();
        $busca = sanitize($this->get('busca'));
        $where = "WHERE 1=1";
        $bind  = [];

        if ($busca) {
            $like   = "%{$busca}%";
            $where .= " AND (l.acao LIKE ? OR l.detalhes LIKE ? OR u.nome LIKE ?)";
            $bind   = [$like, $like, $like];
        }

        $countStmt = $db->prepare("SELECT COUNT(*) FROM logs l LEFT JOIN usuarios u ON l.usuario_id=u.id $where");
        $countStmt->execute($bind);
        $total = (int)$countStmt->fetchColumn();

        $pag   = $this->paginate($total, 30);
        $bind2 = [...$bind, $pag['per_page'], $pag['offset']];

        $stmt = $db->prepare(
            "SELECT l.*, u.nome AS usuario_nome FROM logs l
             LEFT JOIN usuarios u ON l.usuario_id=u.id
             $where ORDER BY l.criado_em DESC LIMIT ? OFFSET ?"
        );
        $stmt->execute($bind2);
        $logs = $stmt->fetchAll();

        $this->view('admin/logs/listar', compact('logs', 'pag', 'busca'));
    }
}
