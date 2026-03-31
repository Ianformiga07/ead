<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle ?? 'Área do Aluno') ?> — <?= APP_NAME ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= APP_URL ?>/public/css/aluno.css">
</head>
<body>
<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg aluno-navbar">
  <div class="container-fluid px-4">
    <a class="navbar-brand" href="<?= APP_URL ?>/aluno/dashboard.php">
      <i class="bi bi-mortarboard-fill me-2"></i><?= APP_NAME ?>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarAluno">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarAluno">
      <ul class="navbar-nav ms-auto align-items-center gap-2">
        <li class="nav-item">
          <a class="nav-link <?= str_contains($_SERVER['PHP_SELF'], 'dashboard') ? 'active' : '' ?>"
             href="<?= APP_URL ?>/aluno/dashboard.php"><i class="bi bi-grid me-1"></i>Meus Cursos</a>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" data-bs-toggle="dropdown">
            <div class="nav-avatar"><i class="bi bi-person-fill"></i></div>
            <?= e(currentUser()['nome']) ?>
          </a>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="<?= APP_URL ?>/aluno/perfil.php"><i class="bi bi-person me-2"></i>Meu Perfil</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-danger" href="<?= APP_URL ?>/logout.php"><i class="bi bi-power me-2"></i>Sair</a></li>
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
  <div class="alert alert-<?= $flash['tipo'] === 'success' ? 'success' : ($flash['tipo'] === 'error' ? 'danger' : 'warning') ?> alert-dismissible fade show">
    <?= e($flash['msg']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  <?php endif; ?>
