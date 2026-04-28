<?php $pageTitle = 'Certificados'; ?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 style="font-size:22px;font-weight:800;margin:0;color:var(--primary)">
    <i class="bi bi-award-fill me-2"></i>Certificados Emitidos
  </h1>
</div>
<div class="data-card mb-4">
  <div class="p-3">
    <form method="GET" class="row g-2">
      <div class="col-md-9"><input type="text" name="busca" class="form-control" placeholder="Buscar por aluno, curso ou código..." value="<?= e($busca) ?>"></div>
      <div class="col-md-3 d-flex gap-2">
        <button type="submit" class="btn btn-primary flex-grow-1"><i class="bi bi-search me-1"></i>Buscar</button>
        <?php if($busca): ?><a href="<?= APP_URL ?>/admin/certificados" class="btn btn-outline-secondary"><i class="bi bi-x"></i></a><?php endif; ?>
      </div>
    </form>
  </div>
</div>
<div class="data-card">
  <div class="data-card-header"><h6 class="data-card-title"><?= number_format($pag['total']) ?> certificado(s)</h6></div>
  <div class="table-responsive">
    <table class="table table-ead">
      <thead><tr><th>Aluno</th><th>Curso</th><th>C/H</th><th>Código</th><th>Emitido</th><th class="text-end">Ações</th></tr></thead>
      <tbody>
      <?php if($certificados): foreach($certificados as $c): ?>
      <tr>
        <td><strong><?= e($c['aluno_nome']) ?></strong><br><small class="text-muted"><?= e($c['crmv'] ?? '') ?></small></td>
        <td><?= e($c['curso_nome']) ?></td>
        <td><?= (int)$c['carga_horaria'] ?>h</td>
        <td><code style="font-size:11px"><?= e(substr($c['codigo'],0,16)).'...' ?></code></td>
        <td><?= dataBR($c['emitido_em']) ?></td>
        <td class="text-end">
          <a href="<?= APP_URL ?>/validar/<?= urlencode($c['codigo']) ?>" target="_blank" class="btn btn-sm btn-outline-success">
            <i class="bi bi-patch-check"></i>
          </a>
          <form method="POST" action="<?= APP_URL ?>/admin/certificados/<?= $c['id'] ?>/deletar" class="d-inline" onsubmit="return confirm('Remover certificado?')">
            <?= csrfField() ?>
            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
          </form>
        </td>
      </tr>
      <?php endforeach; else: ?>
      <tr><td colspan="6" class="text-center text-muted py-5">Nenhum certificado emitido.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
