<?php
/**
 * app/controllers/AuthController.php
 * Autenticação: login e logout
 */

class AuthController extends BaseController
{
    public function login(array $params = []): void
    {
        // Já logado → redireciona
        if (isLoggedIn()) {
            $this->redirectByPerfil();
        }

        $erro = '';

        if ($this->isPost()) {
            $this->csrfVerify();

            $email = sanitize($this->post('email'));
            $senha = $this->post('senha');

            if ($email && $senha) {
                $model   = new UsuarioModel();
                $usuario = $model->findByEmail($email);

                if ($usuario && password_verify($senha, $usuario['senha'])) {
                    // Regenera session ID (proteção contra session fixation)
                    session_regenerate_id(true);

                    $_SESSION['usuario_id'] = $usuario['id'];
                    $_SESSION['nome']       = $usuario['nome'];
                    $_SESSION['perfil']     = $usuario['perfil'];
                    $_SESSION['email']      = $usuario['email'];

                    logAction('login', "Login: {$usuario['email']}");

                    $this->redirectByPerfil();
                }

                // Pequeno delay para dificultar brute force
                usleep(300_000);
                $erro = 'E-mail ou senha inválidos.';

            } else {
                $erro = 'Preencha todos os campos.';
            }
        }

        $this->view('auth/login', compact('erro'));
    }

    public function logout(array $params = []): void
    {
        logAction('logout', 'Sessão encerrada');

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }

        session_destroy();
        $this->redirect(APP_URL . '/login');
    }

    // ── Privado ───────────────────────────────────────────────

    private function redirectByPerfil(): never
    {
        $perfil = $_SESSION['perfil'] ?? '';
        if (in_array($perfil, ['admin', 'operador'])) {
            $this->redirect(APP_URL . '/admin/dashboard');
        } else {
            $this->redirect(APP_URL . '/aluno/dashboard');
        }
    }
}
