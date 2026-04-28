<?php
/**
 * app/controllers/admin/MaterialController.php
 */

class MaterialController extends BaseController
{
    private MaterialModel $model;

    public function __construct()
    {
        $this->model = new MaterialModel();
    }

    public function salvar(array $params = []): void
    {
        $this->authAdmin();
        $this->csrfVerify();

        $cursoId = (int)($params['id'] ?? 0);

        if (empty($_FILES['arquivo']['name'])) {
            $this->error('Selecione um arquivo.');
            $this->redirect(APP_URL . "/admin/cursos/{$cursoId}?tab=materiais");
        }

        $nome = uploadFile($_FILES['arquivo'], MAT_PATH, ALLOWED_MATERIAL);
        if (!$nome) {
            $this->error('Tipo ou tamanho de arquivo inválido.');
            $this->redirect(APP_URL . "/admin/cursos/{$cursoId}?tab=materiais");
        }

        $this->model->criar([
            'curso_id' => $cursoId,
            'titulo'   => sanitize($this->post('titulo', $_FILES['arquivo']['name'])),
            'arquivo'  => $nome,
            'tipo'     => strtolower(pathinfo($_FILES['arquivo']['name'], PATHINFO_EXTENSION)),
            'tamanho'  => $_FILES['arquivo']['size'],
        ]);

        logAction('material.salvar', "Curso {$cursoId}");
        $this->success('Material enviado!');
        $this->redirect(APP_URL . "/admin/cursos/{$cursoId}?tab=materiais");
    }

    public function deletar(array $params = []): void
    {
        $this->authAdmin();
        $this->csrfVerify();

        $cursoId = (int)($params['id']  ?? 0);
        $matId   = (int)($params['mid'] ?? 0);

        $mat = $this->model->findById($matId);
        if ($mat) {
            $file = MAT_PATH . '/' . $mat['arquivo'];
            if (file_exists($file)) unlink($file);
        }

        $this->model->deletar($matId);
        logAction('material.deletar', "Material {$matId}");
        $this->success('Material removido.');
        $this->redirect(APP_URL . "/admin/cursos/{$cursoId}?tab=materiais");
    }
}
