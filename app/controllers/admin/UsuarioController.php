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
            'nome'   => sanitizeName($this->post('nome')),
            'email'  => sanitizeEmail($this->post('email')),
            'perfil' => in_array($this->post('perfil'), ['admin', 'operador'])
                        ? $this->post('perfil') : 'operador',
            'status' => $this->intPost('status', 1),
        ];
        $senha     = $this->post('senha');
        $redirBase = $id ? APP_URL . "/admin/usuarios/{$id}" : APP_URL . '/admin/usuarios/novo';

        // ── Validações ────────────────────────────────────────
        $erros = [];

        if (empty($d['nome']) || strlen($d['nome']) < 3) {
            $erros[] = 'Nome é obrigatório e deve ter ao menos 3 caracteres.';
        }

        if (empty($d['email'])) {
            $erros[] = 'E-mail inválido ou obrigatório.';
        }

        if (!in_array((int)$d['status'], [0, 1], true)) {
            $erros[] = 'Status inválido.';
        }

        if (!$id && empty($senha)) {
            $erros[] = 'Informe uma senha.';
        }

        if (!empty($senha) && strlen($senha) < 6) {
            $erros[] = 'A senha deve ter ao menos 6 caracteres.';
        }

        if (!empty($erros)) {
            $this->error(implode(' | ', $erros));
            $this->redirect($redirBase);
        }

        if ($this->model->emailExiste($d['email'], $id)) {
            $this->error('Este e-mail já está em uso.');
            $this->redirect($redirBase);
        }

        if ($id) {
            if ($senha) $d['senha'] = $senha;
            $this->model->atualizar($id, $d);
            logAction('usuario.salvar', "ID: {$id}");
            $this->success('Usuário atualizado!');
            $this->redirect(APP_URL . "/admin/usuarios/{$id}");
        } else {
            $d['senha'] = $senha;
            $newId = $this->model->criar($d);
            logAction('usuario.salvar', "ID: {$newId}");
            $this->success('Usuário criado!');
            $this->redirect(APP_URL . "/admin/usuarios/{$newId}");
        }
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
