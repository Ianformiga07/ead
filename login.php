<?php
// login.php
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/app/helpers/functions.php';
require_once __DIR__ . '/app/models/Model.php';
require_once __DIR__ . '/app/models/UsuarioModel.php';

if (isLoggedIn()) {
    redirect($_SESSION['perfil'] === 'admin' ? APP_URL . '/admin/dashboard.php' : APP_URL . '/aluno/dashboard.php');
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();
    $email = sanitize($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if ($email && $senha) {
        $model   = new UsuarioModel();
        $usuario = $model->findByEmail($email);

        if ($usuario && password_verify($senha, $usuario['senha'])) {
            session_regenerate_id(true);
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['nome']       = $usuario['nome'];
            $_SESSION['perfil']     = $usuario['perfil'];
            $_SESSION['email']      = $usuario['email'];
            logAction('login', "Login realizado: {$usuario['email']}");
            redirect($usuario['perfil'] === 'admin' ? APP_URL . '/admin/dashboard.php' : APP_URL . '/aluno/dashboard.php');
        } else {
            $erro = 'E-mail ou senha inválidos.';
        }
    } else {
        $erro = 'Preencha todos os campos.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — <?= APP_NAME ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  :root { --primary: #4f46e5; }
  * { box-sizing: border-box; }
  body {
    font-family: 'Plus Jakarta Sans', sans-serif;
    min-height: 100vh;
    background: linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #4338ca 100%);
    display: flex; align-items: center; justify-content: center;
    padding: 20px;
  }
  .login-card {
    width: 100%; max-width: 420px;
    background: #fff;
    border-radius: 20px;
    padding: 40px;
    box-shadow: 0 25px 60px rgba(0,0,0,.3);
  }
  .login-logo {
    text-align: center; margin-bottom: 32px;
  }
  .login-logo .icon-wrap {
    width: 64px; height: 64px;
    background: var(--primary);
    border-radius: 16px;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 30px; color: #fff; margin-bottom: 12px;
  }
  .login-logo h1 { font-size: 22px; font-weight: 700; color: #1e1b4b; margin: 0; }
  .login-logo p  { font-size: 13px; color: #64748b; margin: 4px 0 0; }
  .form-label { font-weight: 600; font-size: 13px; color: #374151; }
  .form-control {
    border: 1.5px solid #e2e8f0; border-radius: 10px;
    padding: 10px 14px; font-size: 14px;
    transition: border-color .2s, box-shadow .2s;
  }
  .form-control:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(79,70,229,.15);
    outline: none;
  }
  .input-group-text {
    border: 1.5px solid #e2e8f0; border-radius: 0 10px 10px 0;
    background: #f8fafc; cursor: pointer;
    border-left: none;
  }
  .input-group .form-control { border-radius: 10px 0 0 10px; }
  .btn-login {
    background: var(--primary); color: #fff;
    border: none; border-radius: 10px;
    padding: 12px; font-weight: 700; font-size: 15px;
    width: 100%; cursor: pointer;
    transition: background .2s, transform .1s;
  }
  .btn-login:hover { background: #3730a3; }
  .btn-login:active { transform: scale(.98); }
  .divider { text-align: center; color: #94a3b8; font-size: 12px; margin: 16px 0; }
  .alert-danger { border-radius: 10px; font-size: 14px; }
  .demo-info {
    background: #f0f9ff; border: 1px solid #bae6fd;
    border-radius: 10px; padding: 12px 16px;
    font-size: 12px; color: #0369a1; margin-top: 20px;
  }
</style>
</head>
<body>
<div class="login-card">
  <div class="login-logo">
    <div class="icon-wrap"><i class="bi bi-mortarboard-fill"></i></div>
    <h1><?= APP_NAME ?></h1>
    <p>Plataforma de Ensino a Distância</p>
  </div>

  <?php if ($erro): ?>
  <div class="alert alert-danger d-flex align-items-center gap-2" role="alert">
    <i class="bi bi-exclamation-triangle-fill"></i>
    <?= e($erro) ?>
  </div>
  <?php endif; ?>

  <form method="POST" action="">
    <?= csrfField() ?>

    <div class="mb-3">
      <label class="form-label">E-mail</label>
      <input type="email" name="email" class="form-control" placeholder="seu@email.com"
             value="<?= e($_POST['email'] ?? '') ?>" required autofocus>
    </div>

    <div class="mb-4">
      <label class="form-label">Senha</label>
      <div class="input-group">
        <input type="password" name="senha" id="senhaInput" class="form-control" placeholder="••••••••" required>
        <span class="input-group-text" onclick="toggleSenha()">
          <i class="bi bi-eye" id="eyeIcon"></i>
        </span>
      </div>
    </div>

    <button type="submit" class="btn-login">
      <i class="bi bi-box-arrow-in-right me-2"></i>Entrar
    </button>
  </form>

  <div class="demo-info">
    <strong>Admin padrão:</strong> admin@ead.com | senha: <code>password</code><br>
    <small>Altere a senha após o primeiro acesso!</small>
  </div>
</div>

<script>
function toggleSenha() {
  const i = document.getElementById('senhaInput');
  const e = document.getElementById('eyeIcon');
  if (i.type === 'password') { i.type = 'text'; e.className = 'bi bi-eye-slash'; }
  else { i.type = 'password'; e.className = 'bi bi-eye'; }
}
</script>
</body>
</html>
