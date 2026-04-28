<?php $pageTitle = 'Dashboard'; ?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
  <div>
    <h1 style="font-size:24px;font-weight:800;margin:0;color:var(--primary)">
      <i class="bi bi-grid-1x2-fill me-2"></i>Dashboard
    </h1>
    <p class="page-subtitle">Bem-vindo ao painel administrativo do <?= APP_NAME ?></p>
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
  <?php
  $cards = [
    ['alunos',       'Alunos Ativos',        'people-fill',           'blue',   '/admin/alunos'],
    ['cursos',       'Cursos Ativos',         'journal-bookmark-fill', 'teal',   '/admin/cursos'],
    ['matriculas',   'Matrículas Ativas',     'person-check-fill',     'green',  '/admin/matriculas'],
    ['certificados', 'Certificados Emitidos', 'award-fill',            'orange', '/admin/certificados'],
  ];
  foreach ($cards as [$key, $label, $icon, $color, $link]):
  ?>
  <div class="col-6 col-xl-3">
    <div class="stat-card <?= $color ?>">
      <div class="stat-icon <?= $color ?>"><i class="bi bi-<?= $icon ?>"></i></div>
      <div class="stat-value"><?= number_format($stats[$key]) ?></div>
      <div class="stat-label"><?= $label ?></div>
      <a href="<?= APP_URL . $link ?>" style="font-size:11px;color:var(--primary);text-decoration:none;display:block;margin-top:8px">
        Ver todos <i class="bi bi-arrow-right"></i>
      </a>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Ações rápidas -->
<div class="row g-3 mb-4">
  <div class="col-12">
    <div class="data-card">
      <div class="data-card-header">
        <h6 class="data-card-title"><i class="bi bi-lightning-fill me-2"></i>Ações Rápidas</h6>
      </div>
      <div class="d-flex gap-3 flex-wrap p-3">
        <a href="<?= APP_URL ?>/admin/cursos/novo" class="btn btn-primary">
          <i class="bi bi-plus-circle me-2"></i>Novo Curso
        </a>
        <a href="<?= APP_URL ?>/admin/alunos/novo" class="btn btn-outline-primary">
          <i class="bi bi-person-plus me-2"></i>Novo Aluno
        </a>
        <a href="<?= APP_URL ?>/admin/matriculas" class="btn btn-outline-primary">
          <i class="bi bi-person-check me-2"></i>Gerenciar Matrículas
        </a>
        <a href="<?= APP_URL ?>/admin/certificados" class="btn btn-outline-warning">
          <i class="bi bi-award me-2"></i>Ver Certificados
        </a>
        <a href="<?= APP_URL ?>/admin/logs" class="btn btn-outline-secondary">
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
        <a href="<?= APP_URL ?>/admin/matriculas" class="btn btn-sm btn-outline-primary">Ver todas</a>
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
            <td><?= badgeStatus($m['status']) ?></td>
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
        <a href="<?= APP_URL ?>/admin/alunos" class="btn btn-sm btn-outline-primary">Ver todos</a>
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
