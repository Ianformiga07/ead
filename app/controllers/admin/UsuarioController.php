<?php
/**
 * app/controllers/admin/UsuarioController.php
 * Gestão de usuários do sistema (admin/operador)
 */

class UsuarioController extends BaseController
{
    private UsuarioModel $model;

    public function __construct()
    {
        $this->model = new UsuarioModel();
    }

    public function index(array $params = []): void
    {
        $this->authSoAdmin();

        $busca   = sanitize($this->get('busca'));
        $total   = $this->model->totalUsuarios($busca);
        $pag     = $this->paginate($total);
        $usuarios = $this->model->listarUsuarios($pag['offset'], $pag['per_page'], $busca);

        $this->view('admin/usuarios/listar', compact('usuarios', 'pag', 'busca'));
    }

    public function novo(array $params = []): void
    {
        $this->authSoAdmin();
        $this->view('admin/usuarios/form', ['usuario' => null]);
    }

    public function detalhe(array $params = []): void
    {
        $this->authSoAdmin();

        $id      = (int)($params['id'] ?? 0);
        $usuario = $this->model->findById($id);

        if (!$usuario || $usuario['perfil'] === 'aluno') {
            $this->error('Usuário não encontrado.');
            $this->redirect(APP_URL . '/admin/usuarios');
        }

        $this->view('admin/usuarios/form', compact('usuario'));
    }

    public function salvar(array $params = []): void
    {
        $this->authSoAdmin();
        $this->csrfVerify();

        $id = (int)($params['id'] ?? 0);

        $d = [
            'nome'   => sanitize($this->post('nome')),
            'email'  => sanitize($this->post('email')),
            'perfil' => in_array($this->post('perfil'), ['admin', 'operador'])
                        ? $this->post('perfil') : 'operador',
            'status' => $this->intPost('status', 1),
        ];
        $senha = $this->post('senha');

        if (empty($d['nome']) || empty($d['email'])) {
            $this->error('Nome e e-mail são obrigatórios.');
            $this->redirect($id ? APP_URL . "/admin/usuarios/{$id}" : APP_URL . '/admin/usuarios/novo');
        }

        if ($this->model->emailExiste($d['email'], $id)) {
            $this->error('Este e-mail já está em uso.');
            $this->redirect($id ? APP_URL . "/admin/usuarios/{$id}" : APP_URL . '/admin/usuarios/novo');
        }

        if ($id) {
            if ($senha) $d['senha'] = $senha;
            $this->model->atualizar($id, $d);
            $this->success('Usuário atualizado!');
            $this->redirect(APP_URL . "/admin/usuarios/{$id}");
        } else {
            if (empty($senha)) {
                $this->error('Informe uma senha.');
                $this->redirect(APP_URL . '/admin/usuarios/novo');
            }
            $d['senha'] = $senha;
            $newId = $this->model->criar($d);
            $this->success('Usuário criado!');
            $this->redirect(APP_URL . "/admin/usuarios/{$newId}");
        }

        logAction('usuario.salvar', "ID: " . ($id ?: 'novo'));
    }

    public function deletar(array $params = []): void
    {
        $this->authSoAdmin();
        $this->csrfVerify();

        $id = (int)($params['id'] ?? 0);
        $this->model->deletar($id);
        logAction('usuario.deletar', "ID {$id}");
        $this->success('Usuário desativado.');
        $this->redirect(APP_URL . '/admin/usuarios');
    }
}
