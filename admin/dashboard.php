<?php
require_once __DIR__ . '/../app/bootstrap.php';
authCheck('admin');

$db = getDB();
$stats = [
    'alunos'     => $db->query("SELECT COUNT(*) FROM usuarios WHERE perfil='aluno' AND status=1")->fetchColumn(),
    'cursos'     => $db->query("SELECT COUNT(*) FROM cursos WHERE status=1")->fetchColumn(),
    'matriculas' => $db->query("SELECT COUNT(*) FROM matriculas WHERE status='ativa'")->fetchColumn(),
    'certificados'=> $db->query("SELECT COUNT(*) FROM certificados")->fetchColumn(),
];

$ultimosAlunos = $db->query(
    "SELECT u.nome, u.email, u.criado_em FROM usuarios u WHERE u.perfil='aluno' ORDER BY u.criado_em DESC LIMIT 5"
)->fetchAll();

$ultimasMatriculas = $db->query(
    "SELECT u.nome as aluno, c.nome as curso, m.matriculado_em, m.status
     FROM matriculas m JOIN usuarios u ON m.aluno_id=u.id JOIN cursos c ON m.curso_id=c.id
     ORDER BY m.matriculado_em DESC LIMIT 6"
)->fetchAll();

$pageTitle = 'Dashboard';
include __DIR__ . '/../app/views/layouts/admin_header.php';
?>
<div class="page-header">
  <div>
    <h1>Dashboard</h1>
    <p class="page-subtitle">Visão geral da plataforma</p>
  </div>
  <span class="text-muted small"><i class="bi bi-calendar3 me-1"></i><?= date('d/m/Y') ?></span>
</div>

<!-- STATS -->
<div class="row g-3 mb-4">
  <div class="col-6 col-xl-3">
    <div class="stat-card">
      <div class="stat-icon purple"><i class="bi bi-people-fill"></i></div>
      <div class="stat-value"><?= number_format($stats['alunos']) ?></div>
      <div class="stat-label">Alunos ativos</div>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="stat-card">
      <div class="stat-icon blue"><i class="bi bi-journal-bookmark-fill"></i></div>
      <div class="stat-value"><?= number_format($stats['cursos']) ?></div>
      <div class="stat-label">Cursos ativos</div>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="stat-card">
      <div class="stat-icon green"><i class="bi bi-person-check-fill"></i></div>
      <div class="stat-value"><?= number_format($stats['matriculas']) ?></div>
      <div class="stat-label">Matrículas ativas</div>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="stat-card">
      <div class="stat-icon orange"><i class="bi bi-award-fill"></i></div>
      <div class="stat-value"><?= number_format($stats['certificados']) ?></div>
      <div class="stat-label">Certificados emitidos</div>
    </div>
  </div>
</div>

<div class="row g-3">
  <!-- ÚLTIMAS MATRÍCULAS -->
  <div class="col-lg-8">
    <div class="data-card">
      <div class="data-card-header">
        <h6 class="data-card-title"><i class="bi bi-person-check me-2 text-primary"></i>Últimas Matrículas</h6>
        <a href="<?= APP_URL ?>/admin/matriculas.php" class="btn btn-sm btn-outline-primary">Ver todas</a>
      </div>
      <div class="table-responsive">
        <table class="table table-ead">
          <thead><tr><th>Aluno</th><th>Curso</th><th>Data</th><th>Status</th></tr></thead>
          <tbody>
          <?php if ($ultimasMatriculas): foreach ($ultimasMatriculas as $m): ?>
          <tr>
            <td><?= e($m['aluno']) ?></td>
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

  <!-- ÚLTIMOS ALUNOS -->
  <div class="col-lg-4">
    <div class="data-card">
      <div class="data-card-header">
        <h6 class="data-card-title"><i class="bi bi-people me-2 text-primary"></i>Novos Alunos</h6>
        <a href="<?= APP_URL ?>/admin/alunos.php" class="btn btn-sm btn-outline-primary">Ver todos</a>
      </div>
      <ul class="list-group list-group-flush">
        <?php if ($ultimosAlunos): foreach ($ultimosAlunos as $a): ?>
        <li class="list-group-item px-4 py-3">
          <div class="d-flex align-items-center gap-3">
            <div class="avatar" style="width:36px;height:36px;flex-shrink:0;background:var(--primary-light);color:var(--primary);border-radius:50%;display:flex;align-items:center;justify-content:center;">
              <i class="bi bi-person"></i>
            </div>
            <div>
              <div class="fw-semibold" style="font-size:13px"><?= e($a['nome']) ?></div>
              <div class="text-muted" style="font-size:11px"><?= e($a['email']) ?></div>
            </div>
          </div>
        </li>
        <?php endforeach; else: ?>
        <li class="list-group-item text-center text-muted py-4">Nenhum aluno cadastrado.</li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../app/views/layouts/admin_footer.php'; ?>
