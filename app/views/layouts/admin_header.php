<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle ?? 'Painel Admin') ?> — <?= APP_NAME ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= APP_URL ?>/public/css/admin.css">
</head>
<body>
<div class="wrapper">
  <!-- SIDEBAR -->
  <nav id="sidebar" class="sidebar">
    <div class="sidebar-header">
      <div class="logo-wrap">
        <span class="logo-icon"><i class="bi bi-mortarboard-fill"></i></span>
        <span class="logo-text"><?= APP_NAME ?></span>
      </div>
      <button class="sidebar-toggle d-lg-none" id="sidebarToggle"><i class="bi bi-x-lg"></i></button>
    </div>

    <div class="sidebar-profile">
      <div class="avatar"><i class="bi bi-person-circle"></i></div>
      <div>
        <div class="profile-name"><?= e(currentUser()['nome']) ?></div>
        <div class="profile-role">Administrador</div>
      </div>
    </div>

    <ul class="sidebar-nav">
      <li class="nav-label">Principal</li>
      <li><a href="<?= APP_URL ?>/admin/dashboard.php" class="<?= str_contains($_SERVER['PHP_SELF'], 'dashboard') ? 'active' : '' ?>">
        <i class="bi bi-grid-1x2"></i> Dashboard
      </a></li>

      <li class="nav-label">Cursos</li>
      <li><a href="<?= APP_URL ?>/admin/cursos.php" class="<?= str_contains($_SERVER['PHP_SELF'], 'cursos') ? 'active' : '' ?>">
        <i class="bi bi-journal-bookmark"></i> Gerenciar Cursos
      </a></li>
      <li><a href="<?= APP_URL ?>/admin/matriculas.php" class="<?= str_contains($_SERVER['PHP_SELF'], 'matriculas') ? 'active' : '' ?>">
        <i class="bi bi-person-check"></i> Matrículas
      </a></li>

      <li class="nav-label">Pessoas</li>
      <li><a href="<?= APP_URL ?>/admin/alunos.php" class="<?= str_contains($_SERVER['PHP_SELF'], 'alunos') ? 'active' : '' ?>">
        <i class="bi bi-people"></i> Alunos
      </a></li>

      <li class="nav-label">Certificados</li>
      <li><a href="<?= APP_URL ?>/admin/certificados.php" class="<?= str_contains($_SERVER['PHP_SELF'], 'certificados') ? 'active' : '' ?>">
        <i class="bi bi-award"></i> Certificados
      </a></li>

      <li class="nav-label">Sistema</li>
      <li><a href="<?= APP_URL ?>/admin/logs.php" class="<?= str_contains($_SERVER['PHP_SELF'], 'logs') ? 'active' : '' ?>">
        <i class="bi bi-activity"></i> Logs
      </a></li>
      <li><a href="<?= APP_URL ?>/logout.php">
        <i class="bi bi-box-arrow-left"></i> Sair
      </a></li>
    </ul>
  </nav>

  <!-- MAIN CONTENT -->
  <div class="main-content" id="mainContent">
    <!-- TOPBAR -->
    <header class="topbar">
      <button class="btn btn-link sidebar-toggle-btn" id="sidebarToggleBtn">
        <i class="bi bi-list fs-4"></i>
      </button>
      <div class="topbar-right">
        <?php $flash = getFlash(); ?>
        <span class="text-muted small"><?= date('d/m/Y H:i') ?></span>
        <a href="<?= APP_URL ?>/logout.php" class="btn btn-sm btn-outline-danger ms-3">
          <i class="bi bi-power"></i> Sair
        </a>
      </div>
    </header>

    <!-- PAGE CONTENT -->
    <div class="page-content">
      <?php if ($flash): ?>
      <div class="alert alert-<?= $flash['tipo'] === 'success' ? 'success' : ($flash['tipo'] === 'error' ? 'danger' : 'warning') ?> alert-dismissible fade show" role="alert">
        <?= e($flash['msg']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
      <?php endif; ?>
