<?php
/**
 * admin/dashboard.php — CRMV EAD
 * Dashboard institucional com estatísticas e atividade recente
 */
require_once __DIR__ . '/../app/bootstrap.php';
authCheck('admin');

$db = getDB();
$stats = [
    'alunos'      => $db->query("SELECT COUNT(*) FROM usuarios WHERE perfil='aluno' AND status=1")->fetchColumn(),
    'cursos'      => $db->query("SELECT COUNT(*) FROM cursos WHERE status=1")->fetchColumn(),
    'matriculas'  => $db->query("SELECT COUNT(*) FROM matriculas WHERE status='ativa'")->fetchColumn(),
    'certificados'=> $db->query("SELECT COUNT(*) FROM certificados")->fetchColumn(),
];

$ultimasMatriculas = $db->query(
    "SELECT u.nome as aluno, c.nome as curso, m.matriculado_em, m.status
     FROM matriculas m JOIN usuarios u ON m.aluno_id=u.id JOIN cursos c ON m.curso_id=c.id
     ORDER BY m.matriculado_em DESC LIMIT 8"
)->fetchAll();

$ultimosAlunos = $db->query(
    "SELECT u.nome, u.email, u.criado_em FROM usuarios u WHERE u.perfil='aluno' ORDER BY u.criado_em DESC LIMIT 6"
)->fetchAll();

$pageTitle = 'Dashboard';
include __DIR__ . '/../app/views/layouts/admin_header.php';
?>

<!-- Boas-vindas institucional -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
  <div>
    <h1 style="font-size:24px;font-weight:800;margin:0;color:var(--primary)">
      <i class="bi bi-grid-1x2-fill me-2"></i>Dashboard
    </h1>
    <p class="page-subtitle">Bem-vindo ao painel administrativo do CRMV EAD</p>
  </div>
  <div class="text-end">
    <div style="font-size:13px;font-weight:600;color:var(--primary)">CRMV-TO</div>
    <div style="font-size:12px;color:var(--text-muted)">
      <i class="bi bi-calendar3 me-1"></i><?= date('d/m/Y H:i') ?>
    </div>
  </div>
</div>

<!-- Cards de estatísticas -->
<div class="row g-3 mb-4">
  <div class="col-6 col-xl-3">
    <div class="stat-card blue">
      <div class="stat-icon blue"><i class="bi bi-people-fill"></i></div>
      <div class="stat-value"><?= number_format($stats['alunos']) ?></div>
      <div class="stat-label">Alunos Ativos</div>
      <a href="<?= APP_URL ?>/admin/alunos.php" style="font-size:11px;color:var(--primary);text-decoration:none;display:block;margin-top:8px">
        Ver todos <i class="bi bi-arrow-right"></i>
      </a>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="stat-card teal">
      <div class="stat-icon teal"><i class="bi bi-journal-bookmark-fill"></i></div>
      <div class="stat-value"><?= number_format($stats['cursos']) ?></div>
      <div class="stat-label">Cursos Ativos</div>
      <a href="<?= APP_URL ?>/admin/cursos.php" style="font-size:11px;color:#0891b2;text-decoration:none;display:block;margin-top:8px">
        Ver todos <i class="bi bi-arrow-right"></i>
      </a>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="stat-card green">
      <div class="stat-icon green"><i class="bi bi-person-check-fill"></i></div>
      <div class="stat-value"><?= number_format($stats['matriculas']) ?></div>
      <div class="stat-label">Matrículas Ativas</div>
      <a href="<?= APP_URL ?>/admin/matriculas.php" style="font-size:11px;color:var(--success);text-decoration:none;display:block;margin-top:8px">
        Ver todas <i class="bi bi-arrow-right"></i>
      </a>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="stat-card orange">
      <div class="stat-icon orange"><i class="bi bi-award-fill"></i></div>
      <div class="stat-value"><?= number_format($stats['certificados']) ?></div>
      <div class="stat-label">Certificados Emitidos</div>
      <a href="<?= APP_URL ?>/admin/certificados.php" style="font-size:11px;color:var(--accent);text-decoration:none;display:block;margin-top:8px">
        Ver todos <i class="bi bi-arrow-right"></i>
      </a>
    </div>
  </div>
</div>

<!-- Ações rápidas -->
<div class="row g-3 mb-4">
  <div class="col-12">
    <div class="data-card">
      <div class="data-card-header">
        <h6 class="data-card-title"><i class="bi bi-lightning-fill me-2"></i>Ações Rápidas</h6>
      </div>
      <div class="d-flex gap-3 flex-wrap p-3">
        <a href="<?= APP_URL ?>/admin/cursos.php?acao=novo" class="btn btn-primary">
          <i class="bi bi-plus-circle me-2"></i>Novo Curso
        </a>
        <a href="<?= APP_URL ?>/admin/alunos.php?acao=novo" class="btn btn-outline-primary">
          <i class="bi bi-person-plus me-2"></i>Novo Aluno
        </a>
        <a href="<?= APP_URL ?>/admin/matriculas.php" class="btn btn-outline-primary">
          <i class="bi bi-person-check me-2"></i>Gerenciar Matrículas
        </a>
        <a href="<?= APP_URL ?>/admin/certificados.php" class="btn btn-outline-warning">
          <i class="bi bi-award me-2"></i>Ver Certificados
        </a>
        <a href="<?= APP_URL ?>/admin/logs.php" class="btn btn-outline-secondary">
          <i class="bi bi-activity me-2"></i>Logs do Sistema
        </a>
      </div>
    </div>
  </div>
</div>

<div class="row g-3">
  <!-- Últimas matrículas -->
  <div class="col-lg-8">
    <div class="data-card">
      <div class="data-card-header">
        <h6 class="data-card-title"><i class="bi bi-person-check me-2"></i>Últimas Matrículas</h6>
        <a href="<?= APP_URL ?>/admin/matriculas.php" class="btn btn-sm btn-outline-primary">Ver todas</a>
      </div>
      <div class="table-responsive">
        <table class="table table-ead">
          <thead><tr><th>Aluno</th><th>Curso</th><th>Data</th><th>Status</th></tr></thead>
          <tbody>
          <?php if ($ultimasMatriculas): foreach ($ultimasMatriculas as $m): ?>
          <tr>
            <td><strong><?= e($m['aluno']) ?></strong></td>
            <td><?= e($m['curso']) ?></td>
            <td><?= dataBR($m['matriculado_em']) ?></td>
            <td><span class="badge-status badge-<?= $m['status'] ?>"><?= ucfirst($m['status']) ?></span></td>
          </tr>
          <?php endforeach; else: ?>
          <tr><td colspan="4" class="text-center text-muted py-4">Nenhuma matrícula ainda.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Novos alunos -->
  <div class="col-lg-4">
    <div class="data-card h-100">
      <div class="data-card-header">
        <h6 class="data-card-title"><i class="bi bi-people me-2"></i>Novos Alunos</h6>
        <a href="<?= APP_URL ?>/admin/alunos.php" class="btn btn-sm btn-outline-primary">Ver todos</a>
      </div>
      <?php if ($ultimosAlunos): foreach ($ultimosAlunos as $a): ?>
      <div class="d-flex align-items-center gap-3 px-4 py-3" style="border-bottom:1px solid var(--border)">
        <div style="width:36px;height:36px;flex-shrink:0;background:var(--primary-light);color:var(--primary);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:15px">
          <i class="bi bi-person-fill"></i>
        </div>
        <div class="flex-grow-1 min-w-0">
          <div style="font-size:13px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= e($a['nome']) ?></div>
          <div style="font-size:11px;color:var(--text-muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= e($a['email']) ?></div>
        </div>
        <small class="text-muted" style="font-size:11px;flex-shrink:0"><?= dataBR($a['criado_em']) ?></small>
      </div>
      <?php endforeach; else: ?>
      <div class="empty-state"><i class="bi bi-people"></i><p>Nenhum aluno.</p></div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../app/views/layouts/admin_footer.php'; ?>
