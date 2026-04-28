<?php
/**
 * app/controllers/admin/AvaliacaoController.php
 */

class AvaliacaoController extends BaseController
{
    private AvaliacaoModel $model;

    public function __construct()
    {
        $this->model = new AvaliacaoModel();
    }

    public function salvar(array $params = []): void
    {
        $this->authAdmin();
        $this->csrfVerify();

        $cursoId   = (int)($params['id'] ?? 0);
        $avaliacao = $this->model->porCurso($cursoId);

        $d = [
            'curso_id'   => $cursoId,
            'titulo'     => sanitize($this->post('titulo')),
            'descricao'  => sanitize($this->post('descricao')),
            'tentativas' => $this->intPost('tentativas', 1),
        ];

        if ($avaliacao) {
            $this->model->atualizar($avaliacao['id'], $d);
        } else {
            $this->model->criar($d);
        }

        logAction('avaliacao.salvar', "Curso {$cursoId}");
        $this->success('Avaliação salva!');
        $this->redirect(APP_URL . "/admin/cursos/{$cursoId}?tab=avaliacao");
    }

    public function salvarPergunta(array $params = []): void
    {
        $this->authAdmin();
        $this->csrfVerify();

        $cursoId   = (int)($params['id'] ?? 0);
        $avaliacao = $this->model->porCurso($cursoId);

        if (!$avaliacao) {
            $this->error('Configure a avaliação antes de adicionar perguntas.');
            $this->redirect(APP_URL . "/admin/cursos/{$cursoId}?tab=avaliacao");
        }

        $perguntaId = $this->intPost('pergunta_id');
        $enunciado  = sanitize($this->post('enunciado'));
        $pontos     = (float)$this->post('pontos', 1);
        $ordem      = $this->intPost('ordem', 1);

        $alternativas = $_POST['alternativas']   ?? [];
        $corretas     = $_POST['correta']        ?? [];

        // Cria ou atualiza pergunta
        if ($perguntaId) {
            // deletar alternativas antigas e recriar
            $this->model->deletarAlternativas($perguntaId);
        } else {
            $perguntaId = $this->model->criarPergunta([
                'avaliacao_id' => $avaliacao['id'],
                'enunciado'    => $enunciado,
                'pontos'       => $pontos,
                'ordem'        => $ordem,
            ]);
        }

        // Salva alternativas
        foreach ($alternativas as $i => $texto) {
            if (trim($texto) === '') continue;
            $isCorreta = in_array((string)$i, array_keys($corretas));
            $this->model->criarAlternativa($perguntaId, sanitize($texto), $isCorreta);
        }

        $this->success('Pergunta salva!');
        $this->redirect(APP_URL . "/admin/cursos/{$cursoId}?tab=avaliacao");
    }

    public function deletarPergunta(array $params = []): void
    {
        $this->authAdmin();
        $this->csrfVerify();

        $cursoId    = (int)($params['id']  ?? 0);
        $perguntaId = (int)($params['pid'] ?? 0);

        $this->model->deletarAlternativas($perguntaId);
        $this->model->deletarPergunta($perguntaId);
        logAction('pergunta.deletar', "Pergunta {$perguntaId}");
        $this->success('Pergunta removida.');
        $this->redirect(APP_URL . "/admin/cursos/{$cursoId}?tab=avaliacao");
    }
}
