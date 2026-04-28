<?php
/**
 * app/controllers/admin/CursoController.php
 * CRUD completo de cursos (config, aulas, materiais, avaliação, certificado)
 */

class CursoController extends BaseController
{
    private CursoModel      $cursoModel;
    private AulaModel       $aulaModel;
    private AvaliacaoModel  $avalModel;
    private MaterialModel   $matModel;
    private CertificadoModel $certModel;

    public function __construct()
    {
        $this->cursoModel = new CursoModel();
        $this->aulaModel  = new AulaModel();
        $this->avalModel  = new AvaliacaoModel();
        $this->matModel   = new MaterialModel();
        $this->certModel  = new CertificadoModel();
    }

    // ── Listagem ──────────────────────────────────────────────
    public function index(array $params = []): void
    {
        $this->authAdmin();

        $busca = sanitize($this->get('busca'));
        $tipo  = sanitize($this->get('tipo'));
        $total = $this->cursoModel->total($busca, $tipo);
        $pag   = $this->paginate($total);
        $cursos = $this->cursoModel->listar($pag['offset'], $pag['per_page'], $busca, $tipo);

        $this->view('admin/cursos/listar', compact('cursos', 'pag', 'busca', 'tipo'));
    }

    // ── Formulário novo ───────────────────────────────────────
    public function novo(array $params = []): void
    {
        $this->authAdmin();
        $this->view('admin/cursos/form', ['curso' => null]);
    }

    // ── Detalhe / abas ────────────────────────────────────────
    public function detalhe(array $params = []): void
    {
        $this->authAdmin();

        $id    = (int)($params['id'] ?? 0);
        $tab   = sanitize($this->get('tab', 'config'));
        $curso = $this->cursoModel->findById($id);

        if (!$curso) {
            $this->error('Curso não encontrado.');
            $this->redirect(APP_URL . '/admin/cursos');
        }

        $aulas    = $this->aulaModel->porCurso($id);
        $materiais = $this->matModel->porCurso($id);
        $avaliacao = $this->avalModel->porCurso($id);
        $perguntas = $avaliacao ? $this->avalModel->perguntas($avaliacao['id']) : [];
        $perguntasCompletas = [];
        foreach ($perguntas as $p) {
            $p['alternativas'] = $this->avalModel->alternativas($p['id']);
            $perguntasCompletas[] = $p;
        }
        $modelo = $this->certModel->modelo($id);

        $this->view('admin/cursos/detalhe', compact(
            'curso', 'tab', 'aulas', 'materiais',
            'avaliacao', 'perguntasCompletas', 'modelo'
        ));
    }

    // ── Salvar (criar/atualizar) ──────────────────────────────
    public function salvar(array $params = []): void
    {
        $this->authAdmin();
        $this->csrfVerify();

        $id = (int)($params['id'] ?? 0);

        $d = [
            'nome'          => sanitize($this->post('nome')),
            'descricao'     => sanitize($this->post('descricao')),
            'tipo'          => $this->post('tipo', 'ead'),
            'carga_horaria' => $this->intPost('carga_horaria'),
            'status'        => $this->intPost('status', 1),
            'tem_avaliacao' => $this->intPost('tem_avaliacao'),
            'nota_minima'   => (float)$this->post('nota_minima', 60),
        ];

        // Validação básica
        if (empty($d['nome'])) {
            $this->error('O nome do curso é obrigatório.');
            $this->redirect($id
                ? APP_URL . "/admin/cursos/{$id}"
                : APP_URL . '/admin/cursos/novo');
        }

        // Upload de imagem
        if (!empty($_FILES['imagem']['name'])) {
            $img = uploadFile($_FILES['imagem'], UPLOAD_PATH . '/cursos', ALLOWED_IMAGE);
            if ($img) $d['imagem'] = $img;
        }

        if ($id) {
            $this->cursoModel->atualizar($id, $d);
            $this->criarAvaliacaoSeNecessario($id, $d);
            logAction('curso.atualizar', "Curso ID {$id}");
            $this->success('Curso atualizado com sucesso!');
            $this->redirect(APP_URL . "/admin/cursos/{$id}?tab=config");
        } else {
            $newId = $this->cursoModel->criar($d);
            $this->criarAvaliacaoSeNecessario($newId, $d);
            logAction('curso.criar', "Curso ID {$newId}");
            $this->success('Curso criado! Configure as aulas e o certificado.');
            $this->redirect(APP_URL . "/admin/cursos/{$newId}?tab=config");
        }
    }

    // ── Deletar ───────────────────────────────────────────────
    public function deletar(array $params = []): void
    {
        $this->authAdmin();
        $this->csrfVerify();

        $id = (int)($params['id'] ?? 0);
        $this->cursoModel->deletar($id);
        logAction('curso.deletar', "Curso ID {$id}");
        $this->success('Curso removido.');
        $this->redirect(APP_URL . '/admin/cursos');
    }

    // ── Privado ───────────────────────────────────────────────

    private function criarAvaliacaoSeNecessario(int $cursoId, array $d): void
    {
        if ($d['tem_avaliacao'] && !$this->avalModel->porCurso($cursoId)) {
            $this->avalModel->criar([
                'curso_id'   => $cursoId,
                'titulo'     => 'Avaliação Final — ' . $d['nome'],
                'descricao'  => '',
                'tentativas' => 1,
            ]);
        }
    }
}
