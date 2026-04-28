<?php
// login.php — CRMV EAD
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/app/helpers/functions.php';
require_once __DIR__ . '/app/models/Model.php';
require_once __DIR__ . '/app/models/UsuarioModel.php';

// Se já está logado, redireciona para o painel correto
if (isLoggedIn()) {
    $perfil = $_SESSION['perfil'] ?? '';
    if (in_array($perfil, ['admin', 'operador'])) {
        redirect(APP_URL . '/admin/dashboard.php');
    } else {
        redirect(APP_URL . '/aluno/dashboard.php');
    }
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
            $_SESSION['usuario_id']        = $usuario['id'];
            $_SESSION['nome']              = $usuario['nome'];
            $_SESSION['perfil']            = $usuario['perfil'];
            $_SESSION['email']             = $usuario['email'];
            $_SESSION['_ultima_atividade'] = time();
            logAction('login', "Login: {$usuario['email']}");

            // admin e operador vão para o painel admin
            if (in_array($usuario['perfil'], ['admin', 'operador'])) {
                redirect(APP_URL . '/admin/dashboard.php');
            } else {
                redirect(APP_URL . '/aluno/dashboard.php');
            }
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
<title>Entrar — <?= APP_NAME ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root { --primary: #003d7c; --primary-mid: #005099; --accent: #c8841a; }
* { box-sizing: border-box; }
body {
  font-family: 'Plus Jakarta Sans', sans-serif;
  min-height: 100vh;
  margin: 0;
  display: flex;
  background: #f0f4f9;
}

/* Painel esquerdo institucional */
.login-left {
  flex: 1;
  background: linear-gradient(155deg, #002855 0%, #003d7c 50%, #005099 100%);
  display: flex; flex-direction: column;
  align-items: center; justify-content: center;
  padding: 40px;
  position: relative;
  overflow: hidden;
}
.login-left::before {
  content: '';
  position: absolute; inset: 0;
  background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
}
.login-left-content { position: relative; z-index: 1; text-align: center; max-width: 320px; }

/* Logo circular CRMV */
.crmv-logo-circle {
  width: 100px; height: 100px;
  background: linear-gradient(135deg, rgba(255,255,255,.15), rgba(255,255,255,.05));
  border: 2px solid rgba(255,255,255,.25);
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  margin: 0 auto 24px;
  box-shadow: 0 8px 32px rgba(0,0,0,.25);
}
.crmv-logo-circle svg { width: 54px; height: 54px; fill: white; }

.login-left h1 { color: #fff; font-size: 28px; font-weight: 800; margin-bottom: 8px; }
.login-left .subtitle { color: rgba(255,255,255,.65); font-size: 13px; line-height: 1.6; }
.login-left .divider-line { width: 50px; height: 3px; background: var(--accent); border-radius: 2px; margin: 20px auto; }
.login-left .tagline {
  color: rgba(255,255,255,.85); font-size: 14px; font-weight: 500;
  line-height: 1.6; text-align: center;
}
.login-crmv-badge {
  background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.2);
  border-radius: 24px; padding: 6px 16px; display: inline-block;
  color: rgba(255,255,255,.8); font-size: 11px; font-weight: 700;
  letter-spacing: 1.5px; text-transform: uppercase; margin-top: 28px;
}

/* Painel direito (formulário) */
.login-right {
  width: 440px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  padding: 40px;
  background: #fff;
  box-shadow: -4px 0 24px rgba(0,0,0,.08);
}
.login-form-wrap { width: 100%; max-width: 360px; }
.login-form-title { font-size: 22px; font-weight: 800; color: var(--primary); margin-bottom: 4px; }
.login-form-sub { font-size: 13px; color: #6b7d8f; margin-bottom: 28px; }

.form-label { font-weight: 600; font-size: 13px; color: #1a2a3a; margin-bottom: 5px; }
.form-control {
  border: 1.5px solid #dde6f0; border-radius: 8px;
  padding: 10px 13px; font-size: 14px;
  transition: border-color .2s, box-shadow .2s;
}
.form-control:focus {
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(0,61,124,.12);
  outline: none;
}
.input-group .form-control { border-radius: 8px 0 0 8px; border-right: none; }
.input-group-text {
  border: 1.5px solid #dde6f0; border-left: none; border-radius: 0 8px 8px 0;
  background: #f7fafd; cursor: pointer; color: #6b7d8f;
}
.input-group-text:hover { color: var(--primary); }

.btn-login {
  background: var(--primary); color: #fff; border: none;
  border-radius: 8px; padding: 12px; font-weight: 700;
  font-size: 15px; width: 100%; cursor: pointer;
  transition: background .2s, transform .1s;
  letter-spacing: .3px;
}
.btn-login:hover { background: #002d5c; }
.btn-login:active { transform: scale(.98); }

.alert-danger { border-radius: 8px; font-size: 13px; border-left: 4px solid #ef4444; }
.login-footer { text-align: center; font-size: 11px; color: #9baab8; margin-top: 28px; line-height: 1.6; }

@media (max-width: 768px) {
  body { flex-direction: column; }
  .login-left { min-height: 220px; flex: none; padding: 30px 20px; }
  .login-right { width: 100%; flex: 1; padding: 30px 20px; box-shadow: none; }
  .crmv-logo-circle { width: 72px; height: 72px; }
  .login-left h1 { font-size: 20px; }
}
</style>
</head>
<body>

<!-- Painel esquerdo institucional -->
<div class="login-left">
  <div class="login-left-content">
    <div class="crmv-logo-circle">
      <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
        <path d="M50 8C27 8 8 27 8 50s19 42 42 42 42-19 42-42S73 8 50 8zm0 8c11 0 21 4 29 11L19 79C12 71 8 61 8 50 8 27 27 16 50 16zm0 68c-11 0-21-4-29-11l60-52c7 8 11 18 11 29 0 23-19 34-42 34z"/>
      </svg>
    </div>
    <h1><?= APP_NAME ?></h1>
    <div class="divider-line"></div>
    <p class="tagline">
      Plataforma de Educação Continuada do<br>
      Conselho Regional de Medicina<br>
      Veterinária do Tocantins
    </p>
    <span class="login-crmv-badge">CRMV-TO</span>
  </div>
</div>

<!-- Formulário de login -->
<div class="login-right">
  <div class="login-form-wrap">
    <div class="login-form-title">Acessar Plataforma</div>
    <div class="login-form-sub">Entre com suas credenciais para continuar</div>

    <?php if ($erro): ?>
    <div class="alert alert-danger d-flex align-items-center gap-2 mb-3">
      <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i>
      <span><?= e($erro) ?></span>
    </div>
    <?php endif; ?>

    <?php if (!empty($_GET['timeout'])): ?>
    <div class="alert alert-warning d-flex align-items-center gap-2 mb-3">
      <i class="bi bi-clock-history flex-shrink-0"></i>
      <span>Sua sessão expirou por inatividade. Por favor, faça login novamente.</span>
    </div>
    <?php endif; ?>

    <form method="POST">
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
          <span class="input-group-text" onclick="toggleSenha()" title="Mostrar/ocultar senha">
            <i class="bi bi-eye" id="eyeIcon"></i>
          </span>
        </div>
      </div>
      <button type="submit" class="btn-login">
        <i class="bi bi-box-arrow-in-right me-2"></i>Entrar
      </button>
    </form>

    <div class="login-footer">
      CRMV-TO &copy; <?= date('Y') ?> — Todos os direitos reservados<br>
      Conselho Regional de Medicina Veterinária do Tocantins
    </div>
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