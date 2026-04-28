<?php
/**
 * app/controllers/admin/AulaController.php
 */

class AulaController extends BaseController
{
    private AulaModel $model;

    public function __construct()
    {
        $this->model = new AulaModel();
    }

    public function salvar(array $params = []): void
    {
        $this->authAdmin();
        $this->csrfVerify();

        $cursoId  = (int)($params['id'] ?? 0);
        $aulaId   = $this->intPost('aula_id');
        $tipoAula = $this->post('tipo_aula', 'link');

        $d = [
            'curso_id'  => $cursoId,
            'titulo'    => sanitize($this->post('titulo')),
            'descricao' => sanitize($this->post('descricao')),
            'ordem'     => $this->intPost('ordem', 1),
            'status'    => $this->intPost('status', 1),
            'url_video' => '',
        ];

        if ($tipoAula === 'link') {
            $d['url_video'] = sanitize($this->post('url_video'));
        } elseif ($tipoAula === 'upload' && !empty($_FILES['video_file']['name'])) {
            $nome = uploadFile($_FILES['video_file'], VIDEO_PATH, ALLOWED_VIDEO);
            if ($nome) {
                $d['url_video'] = 'upload:' . $nome;
            }
        }

        if ($aulaId) {
            $this->model->atualizar($aulaId, $d);
            $this->success('Aula atualizada!');
        } else {
            $this->model->criar($d);
            $this->success('Aula adicionada!');
        }

        logAction('aula.salvar', "Curso {$cursoId}");
        $this->redirect(APP_URL . "/admin/cursos/{$cursoId}?tab=aulas");
    }

    public function deletar(array $params = []): void
    {
        $this->authAdmin();
        $this->csrfVerify();

        $cursoId = (int)($params['id']  ?? 0);
        $aulaId  = (int)($params['aid'] ?? 0);

        $this->model->deletar($aulaId);
        logAction('aula.deletar', "Aula {$aulaId}");
        $this->success('Aula removida.');
        $this->redirect(APP_URL . "/admin/cursos/{$cursoId}?tab=aulas");
    }
}
