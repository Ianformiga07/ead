<?php
/**
 * app/controllers/admin/AlunoController.php
 * Gestão de alunos (veterinários)
 */

class AlunoController extends BaseController
{
    private UsuarioModel  $model;
    private MatriculaModel $matModel;

    public function __construct()
    {
        $this->model    = new UsuarioModel();
        $this->matModel = new MatriculaModel();
    }

    public function index(array $params = []): void
    {
        $this->authAdmin();

        $busca = sanitize($this->get('busca'));
        $total = $this->model->totalAlunos($busca);
        $pag   = $this->paginate($total);
        $alunos = $this->model->listar($pag['offset'], $pag['per_page'], $busca);

        $this->view('admin/alunos/listar', compact('alunos', 'pag', 'busca'));
    }

    public function novo(array $params = []): void
    {
        $this->authAdmin();
        $this->view('admin/alunos/form', ['aluno' => null]);
    }

    public function detalhe(array $params = []): void
    {
        $this->authAdmin();

        $id    = (int)($params['id'] ?? 0);
        $aluno = $this->model->findById($id);

        if (!$aluno || $aluno['perfil'] !== 'aluno') {
            $this->error('Aluno não encontrado.');
            $this->redirect(APP_URL . '/admin/alunos');
        }

        $matriculas = $this->matModel->cursosDoAluno($id); // helper no model
        $this->view('admin/alunos/form', compact('aluno', 'matriculas'));
    }

    public function salvar(array $params = []): void
    {
        $this->authAdmin();
        $this->csrfVerify();

        $id = (int)($params['id'] ?? 0);

        $d = [
            'nome'            => sanitize($this->post('nome')),
            'email'           => sanitize($this->post('email')),
            'cpf'             => sanitize($this->post('cpf')),
            'telefone'        => sanitize($this->post('telefone')),
            'crmv'            => sanitize($this->post('crmv')),
            'data_nascimento' => $this->post('data_nascimento') ?: null,
            'sexo'            => $this->post('sexo') ?: null,
            'especialidade'   => sanitize($this->post('especialidade')),
            'cep'             => sanitize($this->post('cep')),
            'logradouro'      => sanitize($this->post('logradouro')),
            'numero'          => sanitize($this->post('numero')),
            'complemento'     => sanitize($this->post('complemento')),
            'bairro'          => sanitize($this->post('bairro')),
            'cidade'          => sanitize($this->post('cidade')),
            'estado'          => sanitize($this->post('estado')),
            'status'          => $this->intPost('status', 1),
            'perfil'          => 'aluno',
        ];

        $senha = $this->post('senha');

        // Validações
        if (empty($d['nome']) || empty($d['email'])) {
            $this->error('Nome e e-mail são obrigatórios.');
            $this->redirect($id ? APP_URL . "/admin/alunos/{$id}" : APP_URL . '/admin/alunos/novo');
        }

        if ($this->model->emailExiste($d['email'], $id)) {
            $this->error('Este e-mail já está cadastrado.');
            $this->redirect($id ? APP_URL . "/admin/alunos/{$id}" : APP_URL . '/admin/alunos/novo');
        }

        if ($id) {
            if ($senha) $d['senha'] = $senha;
            $this->model->atualizar($id, $d);
            logAction('aluno.atualizar', "Aluno ID {$id}");
            $this->success('Aluno atualizado com sucesso!');
            $this->redirect(APP_URL . "/admin/alunos/{$id}");
        } else {
            if (empty($senha)) {
                $this->error('Informe uma senha para o novo aluno.');
                $this->redirect(APP_URL . '/admin/alunos/novo');
            }
            $d['senha'] = $senha;
            $newId = $this->model->criar($d);
            logAction('aluno.criar', "Aluno ID {$newId}");
            $this->success('Aluno cadastrado com sucesso!');
            $this->redirect(APP_URL . "/admin/alunos/{$newId}");
        }
    }

    public function deletar(array $params = []): void
    {
        $this->authAdmin();
        $this->csrfVerify();

        $id = (int)($params['id'] ?? 0);
        $this->model->deletar($id);
        logAction('aluno.deletar', "Aluno ID {$id}");
        $this->success('Aluno desativado.');
        $this->redirect(APP_URL . '/admin/alunos');
    }

    /** AJAX — busca aluno por nome/CPF/email */
    public function buscar(array $params = []): void
    {
        $this->authAdmin();

        $busca  = sanitize($this->get('q'));
        $alunos = $this->model->listar(0, 10, $busca);

        $this->json(array_map(fn($a) => [
            'id'    => $a['id'],
            'nome'  => $a['nome'],
            'email' => $a['email'],
            'crmv'  => $a['crmv'] ?? '',
        ], $alunos));
    }

    // Helper — busca matrículas do aluno (via MatriculaModel)
    private function cursosDoAluno(int $alunoId): array
    {
        return (new CursoModel())->cursosDoAluno($alunoId);
    }
}
