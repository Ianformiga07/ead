<?php
require_once __DIR__ . '/../app/bootstrap.php';
authCheck('admin');

$db   = getDB();
$page = max(1,(int)($_GET['p'] ?? 1));
$lim  = 30;
$off  = ($page-1)*$lim;
$total= $db->query("SELECT COUNT(*) FROM logs")->fetchColumn();
$pag  = paginate($total, $lim, $page);
$logs = $db->prepare("SELECT l.*, u.nome FROM logs l LEFT JOIN usuarios u ON l.usuario_id=u.id ORDER BY l.criado_em DESC LIMIT ? OFFSET ?");
$logs->execute([$lim, $off]);
$logs = $logs->fetchAll();

$pageTitle = 'Logs do Sistema';
include __DIR__ . '/../app/views/layouts/admin_header.php';
?>

<div class="page-header">
  <h1>Logs do Sistema</h1>
  <p class="page-subtitle"><?= $pag['total'] ?> registro(s)</p>
</div>

<div class="data-card">
  <div class="table-responsive">
    <table class="table table-ead">
      <thead><tr><th>Data/Hora</th><th>Usuário</th><th>Ação</th><th>Detalhes</th><th>IP</th></tr></thead>
      <tbody>
      <?php foreach ($logs as $l): ?>
      <tr>
        <td><?= date('d/m/Y H:i', strtotime($l['criado_em'])) ?></td>
        <td><?= e($l['nome'] ?? 'Sistema') ?></td>
        <td><code><?= e($l['acao']) ?></code></td>
        <td><?= e($l['detalhes'] ?: '—') ?></td>
        <td><?= e($l['ip'] ?? '—') ?></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if ($pag['pages'] > 1): ?>
<nav class="mt-3"><ul class="pagination pagination-sm justify-content-center">
  <?php for ($i=1;$i<=$pag['pages'];$i++): ?>
  <li class="page-item <?= $i==$page?'active':'' ?>"><a class="page-link" href="?p=<?= $i ?>"><?= $i ?></a></li>
  <?php endfor; ?>
</ul></nav>
<?php endif; ?>

<?php include __DIR__ . '/../app/views/layouts/admin_footer.php'; ?>
