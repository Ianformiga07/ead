<?php $pageTitle = 'Logs do Sistema'; ?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 style="font-size:22px;font-weight:800;margin:0;color:var(--primary)">
    <i class="bi bi-activity me-2"></i>Logs do Sistema
  </h1>
</div>
<div class="data-card mb-4">
  <div class="p-3">
    <form method="GET" class="row g-2">
      <div class="col-md-9"><input type="text" name="busca" class="form-control" placeholder="Buscar por ação, detalhe ou usuário..." value="<?= e($busca) ?>"></div>
      <div class="col-md-3 d-flex gap-2">
        <button type="submit" class="btn btn-primary flex-grow-1"><i class="bi bi-search me-1"></i>Buscar</button>
        <?php if($busca): ?><a href="<?= APP_URL ?>/admin/logs" class="btn btn-outline-secondary"><i class="bi bi-x"></i></a><?php endif; ?>
      </div>
    </form>
  </div>
</div>
<div class="data-card">
  <div class="table-responsive">
    <table class="table table-ead">
      <thead><tr><th>Data/Hora</th><th>Usuário</th><th>Ação</th><th>Detalhes</th><th>IP</th></tr></thead>
      <tbody>
      <?php if($logs): foreach($logs as $l): ?>
      <tr>
        <td style="font-size:12px;white-space:nowrap"><?= dataHoraBR($l['criado_em']) ?></td>
        <td><?= e($l['usuario_nome'] ?? 'Sistema') ?></td>
        <td><code style="font-size:12px"><?= e($l['acao']) ?></code></td>
        <td style="font-size:12px"><?= e($l['detalhes']) ?></td>
        <td style="font-size:12px"><?= e($l['ip']) ?></td>
      </tr>
      <?php endforeach; else: ?>
      <tr><td colspan="5" class="text-center text-muted py-5">Nenhum log.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
