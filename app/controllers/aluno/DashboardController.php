<?php
/**
 * app/controllers/aluno/DashboardController.php
 */

class DashboardController extends BaseController
{
    public function index(array $params = []): void
    {
        $this->authAluno();

        $alunoId = $this->userId();
        $cursos  = (new CursoModel())->cursosDoAluno($alunoId);

        $progresso = [];
        $aulaModel = new AulaModel();
        foreach ($cursos as $c) {
            $total     = $aulaModel->totalPorCurso($c['id']);
            $assistidas = count($aulaModel->assistidas($alunoId, $c['id']));
            $progresso[$c['id']] = $total > 0 ? round($assistidas / $total * 100) : 0;
        }

        $this->view('aluno/dashboard', compact('cursos', 'progresso'));
    }
}
