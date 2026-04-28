<?php
/**
 * app/controllers/aluno/AvaliacaoController.php
 */

class AvaliacaoController extends BaseController
{
    public function index(array $params = []): void
    {
        $this->authAluno();

        $alunoId = $this->userId();
        $cursoId = (int)($params['id'] ?? 0);

        $avalModel  = new AvaliacaoModel();
        $matModel   = new MatriculaModel();
        $cursoModel = new CursoModel();

        $curso     = $cursoModel->findById($cursoId);
        $matricula = $matModel->buscar($alunoId, $cursoId);

        if (!$curso || !$matricula || $matricula['status'] === 'cancelada') {
            $this->redirect(APP_URL . '/aluno/dashboard');
        }

        $avaliacao = $avalModel->porCurso($cursoId);

        if (!$avaliacao) {
            $this->error('Este curso não possui avaliação.');
            $this->redirect(APP_URL . "/aluno/cursos/{$cursoId}");
        }

        $tentativas = $avalModel->tentativasAluno($alunoId, $avaliacao['id']);
        $ultima     = $avalModel->ultimaTentativa($alunoId, $avaliacao['id']);

        if ($this->isPost()) {
            $this->csrfVerify();

            if ($tentativas >= $avaliacao['tentativas']) {
                $this->error('Número máximo de tentativas atingido.');
                $this->redirect(APP_URL . "/aluno/cursos/{$cursoId}/avaliacao");
            }

            $perguntas = $avalModel->perguntas($avaliacao['id']);
            $totalPts  = 0;
            $acertos   = 0;

            foreach ($perguntas as $p) {
                $totalPts += $p['pontos'];
                $altId    = (int)($_POST['q_' . $p['id']] ?? 0);
                if (!$altId) continue;

                $alt = $avalModel->findAlternativa($altId);
                if ($alt && $alt['correta']) {
                    $acertos += $p['pontos'];
                }
            }

            $nota     = $totalPts > 0 ? round($acertos / $totalPts * 100, 1) : 0;
            $aprovado = $nota >= ($curso['nota_minima'] ?? 60);

            $tentId = $avalModel->registrarTentativa($alunoId, $avaliacao['id'], $nota, $aprovado);

            // Registra respostas individualmente
            foreach ($perguntas as $p) {
                $altId = (int)($_POST['q_' . $p['id']] ?? 0);
                if (!$altId) continue;
                $alt = $avalModel->findAlternativa($altId);
                $avalModel->registrarResposta($tentId, $p['id'], $altId, (bool)($alt['correta'] ?? false));
            }

            if ($aprovado) {
                $matModel->concluir($alunoId, $cursoId);
            }

            $this->success($aprovado
                ? "Parabéns! Você foi aprovado com nota {$nota}."
                : "Nota: {$nota}. Você não atingiu a nota mínima.");

            $this->redirect(APP_URL . "/aluno/cursos/{$cursoId}/avaliacao");
        }

        // GET — exibe formulário
        $perguntas = $avalModel->perguntas($avaliacao['id']);
        $perguntasCompletas = [];
        foreach ($perguntas as $p) {
            $p['alternativas'] = $avalModel->alternativas($p['id']);
            $perguntasCompletas[] = $p;
        }

        $this->view('aluno/avaliacao', compact(
            'curso', 'avaliacao', 'perguntasCompletas',
            'tentativas', 'ultima', 'matricula'
        ));
    }
}
