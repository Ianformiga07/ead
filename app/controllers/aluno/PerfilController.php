<?php
/**
 * app/controllers/aluno/PerfilController.php
 */

class PerfilController extends BaseController
{
    public function index(array $params = []): void
    {
        $this->authAluno();

        $model   = new UsuarioModel();
        $usuario = $model->findById($this->userId());

        if ($this->isPost()) {
            $this->csrfVerify();

            $d = [
                'nome'            => sanitize($this->post('nome')),
                'email'           => sanitize($this->post('email')),
                'telefone'        => sanitize($this->post('telefone')),
                'cep'             => sanitize($this->post('cep')),
                'logradouro'      => sanitize($this->post('logradouro')),
                'numero'          => sanitize($this->post('numero')),
                'complemento'     => sanitize($this->post('complemento')),
                'bairro'          => sanitize($this->post('bairro')),
                'cidade'          => sanitize($this->post('cidade')),
                'estado'          => sanitize($this->post('estado')),
                'especialidade'   => sanitize($this->post('especialidade')),
            ];

            $senha = $this->post('senha');
            if ($senha) {
                if (strlen($senha) < 6) {
                    $this->error('A senha deve ter no mínimo 6 caracteres.');
                    $this->redirect(APP_URL . '/aluno/perfil');
                }
                $d['senha'] = $senha;
            }

            // Mantém dados que aluno não edita
            $d['cpf']             = $usuario['cpf']             ?? null;
            $d['crmv']            = $usuario['crmv']            ?? null;
            $d['data_nascimento'] = $usuario['data_nascimento'] ?? null;
            $d['sexo']            = $usuario['sexo']            ?? null;
            $d['status']          = 1;

            $model->atualizar($this->userId(), $d);

            // Atualiza nome na sessão
            $_SESSION['nome']  = $d['nome'];
            $_SESSION['email'] = $d['email'];

            $this->success('Perfil atualizado com sucesso!');
            $this->redirect(APP_URL . '/aluno/perfil');
        }

        $this->view('aluno/perfil', compact('usuario'));
    }
}
