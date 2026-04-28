<?php $pageTitle = 'Alunos'; ?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h1 style="font-size:22px;font-weight:800;margin:0;color:var(--primary)">
      <i class="bi bi-people-fill me-2"></i>Veterinários / Alunos
    </h1>
    <p class="page-subtitle">Gerencie os alunos cadastrados</p>
  </div>
  <a href="<?= APP_URL ?>/admin/alunos/novo" class="btn btn-primary">
    <i class="bi bi-person-plus me-1"></i>Novo Aluno
  </a>
</div>
<div class="data-card mb-4">
  <div class="p-3">
    <form method="GET" class="row g-2">
      <div class="col-md-9">
        <input type="text" name="busca" class="form-control" placeholder="Buscar por nome, e-mail, CPF ou CRMV..." value="<?= e($busca) ?>">
      </div>
      <div class="col-md-3 d-flex gap-2">
        <button type="submit" class="btn btn-primary flex-grow-1"><i class="bi bi-search me-1"></i>Buscar</button>
        <?php if($busca): ?><a href="<?= APP_URL ?>/admin/alunos" class="btn btn-outline-secondary"><i class="bi bi-x"></i></a><?php endif; ?>
      </div>
    </form>
  </div>
</div>
<div class="data-card">
  <div class="data-card-header">
    <h6 class="data-card-title"><?= number_format($pag['total']) ?> aluno(s) encontrado(s)</h6>
  </div>
  <div class="table-responsive">
    <table class="table table-ead">
      <thead><tr><th>Nome</th><th>E-mail</th><th>CRMV</th><th>Status</th><th class="text-end">Ações</th></tr></thead>
      <tbody>
      <?php if($alunos): foreach($alunos as $a): ?>
      <tr>
        <td><strong><?= e($a['nome']) ?></strong></td>
        <td><?= e($a['email']) ?></td>
        <td><?= e($a['crmv'] ?? '—') ?></td>
        <td><?= $a['status'] ? '<span class="badge bg-success">Ativo</span>' : '<span class="badge bg-danger">Inativo</span>' ?></td>
        <td class="text-end">
          <a href="<?= APP_URL ?>/admin/alunos/<?= $a['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
          <form method="POST" action="<?= APP_URL ?>/admin/alunos/<?= $a['id'] ?>/deletar" class="d-inline" onsubmit="return confirm('Desativar aluno?')">
            <?= csrfField() ?>
            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
          </form>
        </td>
      </tr>
      <?php endforeach; else: ?>
      <tr><td colspan="5" class="text-center text-muted py-5">Nenhum aluno encontrado.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if($pag['pages']>1): ?>
  <div class="d-flex justify-content-center py-3">
    <nav><ul class="pagination pagination-sm mb-0">
      <?php if($pag['prev']): ?><li class="page-item"><a class="page-link" href="?<?= http_build_query(array_merge($_GET,['page'=>$pag['prev']])) ?>"><i class="bi bi-chevron-left"></i></a></li><?php endif; ?>
      <?php for($i=max(1,$pag['current']-2);$i<=min($pag['pages'],$pag['current']+2);$i++): ?>
      <li class="page-item <?= $i===$pag['current']?'active':'' ?>"><a class="page-link" href="?<?= http_build_query(array_merge($_GET,['page'=>$i])) ?>"><?= $i ?></a></li>
      <?php endfor; ?>
      <?php if($pag['next']): ?><li class="page-item"><a class="page-link" href="?<?= http_build_query(array_merge($_GET,['page'=>$pag['next']])) ?>"><i class="bi bi-chevron-right"></i></a></li><?php endif; ?>
    </ul></nav>
  </div>
  <?php endif; ?>
</div>
