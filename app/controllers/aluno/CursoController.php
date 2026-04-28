<?php
/**
 * app/controllers/aluno/CursoController.php
 */

class CursoController extends BaseController
{
    public function index(array $params = []): void
    {
        $this->authAluno();

        $alunoId  = $this->userId();
        $cursoId  = (int)($params['id'] ?? 0);

        $cursoModel = new CursoModel();
        $aulaModel  = new AulaModel();
        $matModel   = new MaterialModel();
        $matModel2  = new MatriculaModel();

        $curso    = $cursoModel->findById($cursoId);
        $matricula = $matModel2->buscar($alunoId, $cursoId);

        if (!$curso || !$matricula || $matricula['status'] === 'cancelada') {
            $this->error('Você não tem acesso a este curso.');
            $this->redirect(APP_URL . '/aluno/dashboard');
        }

        $aulas      = $aulaModel->porCurso($cursoId);
        $assistidas = $aulaModel->assistidas($alunoId, $cursoId);
        $materiais  = $matModel->porCurso($cursoId);

        // Progresso
        $totalAulas  = count($aulas);
        $progresso   = $totalAulas > 0 ? round(count($assistidas) / $totalAulas * 100) : 0;

        $this->view('aluno/curso', compact(
            'curso', 'matricula', 'aulas', 'assistidas', 'materiais', 'progresso'
        ));
    }

    public function marcarAula(array $params = []): void
    {
        $this->authAluno();
        $this->csrfVerify();

        $alunoId  = $this->userId();
        $cursoId  = (int)($params['id'] ?? 0);
        $aulaId   = $this->intPost('aula_id');

        $aulaModel  = new AulaModel();
        $matModel   = new MatriculaModel();

        $aulaModel->marcarAssistida($alunoId, $aulaId);

        // Recalcula progresso
        $aulas      = $aulaModel->porCurso($cursoId);
        $assistidas = $aulaModel->assistidas($alunoId, $cursoId);
        $total      = count($aulas);
        $prog       = $total > 0 ? round(count($assistidas) / $total * 100) : 0;

        $matModel->atualizarProgresso($alunoId, $cursoId, $prog);

        // Se 100% → concluir automaticamente
        if ($prog >= 100) {
            $matModel->concluir($alunoId, $cursoId);
        }

        if ($this->isAjax()) {
            $this->json(['ok' => true, 'progresso' => $prog]);
        }

        $this->redirect(APP_URL . "/aluno/cursos/{$cursoId}");
    }
}
