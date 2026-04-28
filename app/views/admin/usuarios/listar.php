<?php $pageTitle = 'Usuários do Sistema'; ?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h1 style="font-size:22px;font-weight:800;margin:0;color:var(--primary)">
      <i class="bi bi-shield-person-fill me-2"></i>Usuários do Sistema
    </h1>
    <p class="page-subtitle">Administradores e operadores</p>
  </div>
  <a href="<?= APP_URL ?>/admin/usuarios/novo" class="btn btn-primary">
    <i class="bi bi-person-plus me-1"></i>Novo Usuário
  </a>
</div>
<div class="data-card">
  <div class="table-responsive">
    <table class="table table-ead">
      <thead><tr><th>Nome</th><th>E-mail</th><th>Perfil</th><th>Status</th><th class="text-end">Ações</th></tr></thead>
      <tbody>
      <?php if($usuarios): foreach($usuarios as $u): ?>
      <tr>
        <td><strong><?= e($u['nome']) ?></strong></td>
        <td><?= e($u['email']) ?></td>
        <td><span class="badge <?= $u['perfil']==='admin'?'bg-danger':'bg-info text-dark' ?>"><?= ucfirst(e($u['perfil'])) ?></span></td>
        <td><?= $u['status']?'<span class="badge bg-success">Ativo</span>':'<span class="badge bg-danger">Inativo</span>' ?></td>
        <td class="text-end">
          <a href="<?= APP_URL ?>/admin/usuarios/<?= $u['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
          <form method="POST" action="<?= APP_URL ?>/admin/usuarios/<?= $u['id'] ?>/deletar" class="d-inline" onsubmit="return confirm('Desativar?')">
            <?= csrfField() ?>
            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
          </form>
        </td>
      </tr>
      <?php endforeach; else: ?>
      <tr><td colspan="5" class="text-center text-muted py-5">Nenhum usuário.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
