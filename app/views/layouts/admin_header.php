<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle ?? 'Painel Admin') ?> — <?= APP_NAME ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= APP_URL ?>/public/css/admin.css">
<!-- SweetAlert2 -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.10.5/sweetalert2.all.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.10.5/sweetalert2.min.css">
</head>
<body>
<div class="wrapper">

  <!-- ═══════════ SIDEBAR ═══════════ -->
  <nav id="sidebar" class="sidebar">

    <!-- Logo CRMV-TO -->
    <div class="sidebar-header">
      <a class="logo-wrap" href="<?= APP_URL ?>/admin/dashboard.php">
        <div class="logo-icon">
          <svg width="22" height="22" viewBox="0 0 100 100" fill="white" xmlns="http://www.w3.org/2000/svg">
            <path d="M50 8C27 8 8 27 8 50s19 42 42 42 42-19 42-42S73 8 50 8zm0 8c11 0 21 4 29 11L19 79C12 71 8 61 8 50 8 27 27 16 50 16zm0 68c-11 0-21-4-29-11l60-52c7 8 11 18 11 29 0 23-19 34-42 34z"/>
          </svg>
        </div>
        <div>
          <div class="logo-text"><?= APP_NAME ?></div>
          <div class="logo-sub">Painel Administrativo</div>
        </div>
      </a>
      <button class="sidebar-toggle d-lg-none" id="sidebarToggle"><i class="bi bi-x-lg"></i></button>
    </div>

    <!-- Perfil -->
    <?php $__me = currentUser(); ?>
    <div class="sidebar-profile">
      <div class="avatar"><i class="bi bi-person-fill"></i></div>
      <div>
        <div class="profile-name"><?= e($__me['nome']) ?></div>
        <div class="profile-role">
          <?php if ($__me['perfil'] === 'admin'): ?>
            <span style="color:#fca5a5"><i class="bi bi-shield-fill me-1"></i>Administrador</span>
          <?php else: ?>
            <span style="color:#7dd3fc"><i class="bi bi-person-badge me-1"></i>Operador</span>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Navegação -->
    <ul class="sidebar-nav">
      <li class="nav-label">Principal</li>
      <li><a href="<?= APP_URL ?>/admin/dashboard.php"
             class="<?= str_contains($_SERVER['PHP_SELF'], 'dashboard') ? 'active' : '' ?>">
        <i class="bi bi-grid-1x2-fill"></i> Dashboard
      </a></li>

      <li class="nav-label">Cursos</li>
      <li><a href="<?= APP_URL ?>/admin/cursos.php"
             class="<?= str_contains($_SERVER['PHP_SELF'], 'cursos') || str_contains($_SERVER['PHP_SELF'], 'aulas') || str_contains($_SERVER['PHP_SELF'], 'avaliacao') || str_contains($_SERVER['PHP_SELF'], 'materiais') ? 'active' : '' ?>">
        <i class="bi bi-journal-bookmark-fill"></i> Gerenciar Cursos
      </a></li>
      <li><a href="<?= APP_URL ?>/admin/matriculas.php"
             class="<?= str_contains($_SERVER['PHP_SELF'], 'matriculas') ? 'active' : '' ?>">
        <i class="bi bi-person-check-fill"></i> Matrículas
      </a></li>

      <li class="nav-label">Pessoas</li>
      <li><a href="<?= APP_URL ?>/admin/alunos.php"
             class="<?= str_contains($_SERVER['PHP_SELF'], 'alunos') ? 'active' : '' ?>">
        <i class="bi bi-people-fill"></i> Veterinários / Alunos
      </a></li>

      <?php if ($__me['perfil'] === 'admin'): ?>
      <li><a href="<?= APP_URL ?>/admin/usuarios.php"
             class="<?= str_contains($_SERVER['PHP_SELF'], 'usuarios') ? 'active' : '' ?>">
        <i class="bi bi-shield-person-fill"></i> Usuários do Sistema
      </a></li>
      <?php endif; ?>

      <li class="nav-label">Certificados</li>
      <li><a href="<?= APP_URL ?>/admin/certificados.php"
             class="<?= str_contains($_SERVER['PHP_SELF'], 'certificados') ? 'active' : '' ?>">
        <i class="bi bi-award-fill"></i> Certificados
      </a></li>

      <li class="nav-label">Relatórios</li>
      <li><a href="<?= APP_URL ?>/admin/relatorios.php"
             class="<?= str_contains($_SERVER['PHP_SELF'], 'relatorios') ? 'active' : '' ?>">
        <i class="bi bi-bar-chart-fill"></i> Relatórios
      </a></li>

      <?php if ($__me['perfil'] === 'admin'): ?>
      <li class="nav-label">Sistema</li>
      <li><a href="<?= APP_URL ?>/admin/logs.php"
             class="<?= str_contains($_SERVER['PHP_SELF'], 'logs') ? 'active' : '' ?>">
        <i class="bi bi-activity"></i> Logs do Sistema
      </a></li>
      <?php endif; ?>

      <li><a href="<?= APP_URL ?>/logout.php">
        <i class="bi bi-box-arrow-left"></i> Sair
      </a></li>
    </ul>

    <div class="sidebar-footer">
      CRMV-TO &copy; <?= date('Y') ?><br>
      Educação Continuada
    </div>
  </nav>

  <!-- ═══════════ MAIN CONTENT ═══════════ -->
  <div class="main-content" id="mainContent">

    <!-- TOPBAR -->
    <header class="topbar">
      <div class="d-flex align-items-center gap-3">
        <button class="sidebar-toggle-btn" id="sidebarToggleBtn">
          <i class="bi bi-list"></i>
        </button>
        <span class="topbar-brand d-none d-md-inline">
          <i class="bi bi-geo-alt me-1"></i>Conselho Regional de Medicina Veterinária do Tocantins
        </span>
      </div>
      <div class="topbar-right">
        <span class="text-muted small d-none d-sm-inline">
          <i class="bi bi-calendar3 me-1"></i><?= date('d/m/Y H:i') ?>
        </span>
        <a href="<?= APP_URL ?>/logout.php" class="btn btn-sm btn-outline-danger">
          <i class="bi bi-power me-1"></i>Sair
        </a>
      </div>
    </header>

    <!-- PAGE CONTENT -->
    <div class="page-content">
      <?php $flash = getFlash(); ?>
      <?php if ($flash): ?>
      <div class="alert alert-<?= $flash['tipo'] === 'success' ? 'success' : ($flash['tipo'] === 'error' ? 'danger' : 'warning') ?> alert-dismissible fade show mb-4" role="alert">
        <i class="bi bi-<?= $flash['tipo'] === 'success' ? 'check-circle' : 'exclamation-triangle' ?>-fill me-2"></i>
        <?= e($flash['msg']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
      <?php endif; ?>
