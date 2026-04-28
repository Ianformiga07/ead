<?php
/**
 * app/controllers/admin/DashboardController.php
 */

class DashboardController extends BaseController
{
    public function index(array $params = []): void
    {
        $this->authAdmin();

        $db = getDB();

        $stats = [
            'alunos'       => (int)$db->query("SELECT COUNT(*) FROM usuarios WHERE perfil='aluno' AND status=1")->fetchColumn(),
            'cursos'       => (int)$db->query("SELECT COUNT(*) FROM cursos WHERE status=1")->fetchColumn(),
            'matriculas'   => (int)$db->query("SELECT COUNT(*) FROM matriculas WHERE status='ativa'")->fetchColumn(),
            'certificados' => (int)$db->query("SELECT COUNT(*) FROM certificados")->fetchColumn(),
        ];

        $ultimasMatriculas = $db->query(
            "SELECT u.nome AS aluno, c.nome AS curso, m.matriculado_em, m.status
             FROM matriculas m
             JOIN usuarios u ON m.aluno_id = u.id
             JOIN cursos   c ON m.curso_id = c.id
             ORDER BY m.matriculado_em DESC LIMIT 8"
        )->fetchAll();

        $ultimosAlunos = $db->query(
            "SELECT nome, email, criado_em FROM usuarios
             WHERE perfil='aluno' ORDER BY criado_em DESC LIMIT 6"
        )->fetchAll();

        $this->view('admin/dashboard', compact('stats', 'ultimasMatriculas', 'ultimosAlunos'));
    }
}
