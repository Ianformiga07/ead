<?php
/**
 * app/controllers/admin/MatriculaController.php
 */

class MatriculaController extends BaseController
{
    private MatriculaModel $model;
    private CursoModel     $cursoModel;
    private UsuarioModel   $usuModel;

    public function __construct()
    {
        $this->model      = new MatriculaModel();
        $this->cursoModel = new CursoModel();
        $this->usuModel   = new UsuarioModel();
    }

    public function index(array $params = []): void
    {
        $this->authAdmin();

        $db     = getDB();
        $busca  = sanitize($this->get('busca'));
        $status = sanitize($this->get('status'));

        $where  = "WHERE 1=1";
        $bind   = [];

        if ($busca) {
            $like   = "%{$busca}%";
            $where .= " AND (u.nome LIKE ? OR c.nome LIKE ?)";
            $bind[] = $like;
            $bind[] = $like;
        }
        if ($status) {
            $where .= " AND m.status = ?";
            $bind[] = $status;
        }

        $countStmt = $db->prepare(
            "SELECT COUNT(*) FROM matriculas m
             JOIN usuarios u ON m.aluno_id=u.id
             JOIN cursos   c ON m.curso_id=c.id
             $where"
        );
        $countStmt->execute($bind);
        $total = (int)$countStmt->fetchColumn();

        $pag    = $this->paginate($total);
        $bind2  = [...$bind, $pag['per_page'], $pag['offset']];

        $stmt = $db->prepare(
            "SELECT m.id, m.status, m.progresso, m.matriculado_em, m.concluido_em,
                    u.nome AS aluno_nome, u.email AS aluno_email, u.crmv,
                    c.nome AS curso_nome, c.tipo AS curso_tipo
             FROM matriculas m
             JOIN usuarios u ON m.aluno_id=u.id
             JOIN cursos   c ON m.curso_id=c.id
             $where
             ORDER BY m.matriculado_em DESC
             LIMIT ? OFFSET ?"
        );
        $stmt->execute($bind2);
        $matriculas = $stmt->fetchAll();

        $cursos = $this->cursoModel->cursosAtivos();

        $this->view('admin/matriculas/listar', compact(
            'matriculas', 'pag', 'busca', 'status', 'cursos'
        ));
    }

    public function salvar(array $params = []): void
    {
        $this->authAdmin();
        $this->csrfVerify();

        $alunoId = $this->intPost('aluno_id');
        $cursoId = $this->intPost('curso_id');

        if (!$alunoId || !$cursoId) {
            $this->error('Selecione aluno e curso.');
            $this->redirect(APP_URL . '/admin/matriculas');
        }

        $existente = $this->model->buscar($alunoId, $cursoId);
        if ($existente && $existente['status'] !== 'cancelada') {
            $this->warning('Aluno já matriculado neste curso.');
            $this->redirect(APP_URL . '/admin/matriculas');
        }

        $this->model->matricular($alunoId, $cursoId);
        logAction('matricula.criar', "Aluno {$alunoId} → Curso {$cursoId}");
        $this->success('Matrícula realizada com sucesso!');
        $this->redirect(APP_URL . '/admin/matriculas');
    }

    public function cancelar(array $params = []): void
    {
        $this->authAdmin();
        $this->csrfVerify();

        $id = (int)($params['id'] ?? 0);
        $this->model->cancelar($id);
        logAction('matricula.cancelar', "Matrícula ID {$id}");
        $this->success('Matrícula cancelada.');
        $this->redirect(APP_URL . '/admin/matriculas');
    }
}
