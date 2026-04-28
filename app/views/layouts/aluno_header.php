<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle ?? 'Área do Aluno') ?> — <?= APP_NAME ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= APP_URL ?>/public/css/aluno.css">
<!-- SweetAlert2 -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.10.5/sweetalert2.all.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.10.5/sweetalert2.min.css">
</head>
<body>

<!-- NAVBAR CRMV EAD -->
<nav class="navbar navbar-expand-lg aluno-navbar">
  <div class="container-fluid px-4">

    <!-- Brand com logo CRMV -->
    <a class="navbar-brand d-flex align-items-center gap-2" href="<?= APP_URL ?>/aluno/dashboard.php">
      <div class="nav-logo-icon">
        <svg width="18" height="18" viewBox="0 0 100 100" fill="white" xmlns="http://www.w3.org/2000/svg">
          <path d="M50 8C27 8 8 27 8 50s19 42 42 42 42-19 42-42S73 8 50 8zm0 8c11 0 21 4 29 11L19 79C12 71 8 61 8 50 8 27 27 16 50 16zm0 68c-11 0-21-4-29-11l60-52c7 8 11 18 11 29 0 23-19 34-42 34z"/>
        </svg>
      </div>
      <span class="nav-brand-text"><?= APP_NAME ?></span>
    </a>

    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarAluno">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarAluno">
      <ul class="navbar-nav ms-auto align-items-center gap-1">
        <li class="nav-item">
          <a class="nav-link <?= str_contains($_SERVER['PHP_SELF'], 'dashboard') ? 'active' : '' ?>"
             href="<?= APP_URL ?>/aluno/dashboard.php">
            <i class="bi bi-grid-fill me-1"></i>Meus Cursos
          </a>
        </li>
        <li class="nav-item dropdown ms-2">
          <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" data-bs-toggle="dropdown">
            <div class="nav-avatar"><i class="bi bi-person-fill"></i></div>
            <span class="d-none d-md-inline"><?= e(explode(' ', currentUser()['nome'])[0]) ?></span>
          </a>
          <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" style="min-width:200px">
            <li>
              <div class="px-3 py-2" style="border-bottom:1px solid #f0f0f0">
                <div style="font-size:13px;font-weight:700;color:#1a2a3a"><?= e(currentUser()['nome']) ?></div>
                <div style="font-size:11px;color:#8898aa"><?= e(currentUser()['email']) ?></div>
              </div>
            </li>
            <li><a class="dropdown-item py-2" href="<?= APP_URL ?>/aluno/perfil.php">
              <i class="bi bi-person me-2 text-primary"></i>Meu Perfil
            </a></li>
            <li><hr class="dropdown-divider my-1"></li>
            <li><a class="dropdown-item py-2 text-danger" href="<?= APP_URL ?>/logout.php">
              <i class="bi bi-power me-2"></i>Sair
            </a></li>
          </ul>
        </li>
      </ul>
    </div>
  </div>
</nav>

<div class="aluno-wrapper">
  <div class="container-fluid px-4 py-4">
    <?php $flash = getFlash(); ?>
    <?php if ($flash): ?>
    <div class="alert alert-<?= $flash['tipo'] === 'success' ? 'success' : ($flash['tipo'] === 'error' ? 'danger' : 'warning') ?> alert-dismissible fade show mb-4">
      <i class="bi bi-<?= $flash['tipo'] === 'success' ? 'check-circle' : 'exclamation-triangle' ?>-fill me-2"></i>
      <?= e($flash['msg']) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
