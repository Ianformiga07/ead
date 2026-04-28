<?php $pageTitle = $usuario ? 'Editar Usuário' : 'Novo Usuário'; ?>
<div class="mb-4">
  <h1 style="font-size:22px;font-weight:800;margin:0;color:var(--primary)"><i class="bi bi-shield-person me-2"></i><?= $pageTitle ?></h1>
  <p class="page-subtitle"><a href="<?= APP_URL ?>/admin/usuarios">Usuários</a> / <?= $pageTitle ?></p>
</div>
<div class="data-card">
  <div class="p-4">
    <form method="POST" action="<?= $usuario ? APP_URL.'/admin/usuarios/'.$usuario['id'].'/salvar' : APP_URL.'/admin/usuarios/salvar' ?>">
      <?= csrfField() ?>
      <div class="row g-3">
        <div class="col-md-6"><label class="form-label fw-semibold">Nome *</label>
          <input type="text" name="nome" class="form-control" value="<?= e($usuario['nome'] ?? '') ?>" required></div>
        <div class="col-md-6"><label class="form-label fw-semibold">E-mail *</label>
          <input type="email" name="email" class="form-control" value="<?= e($usuario['email'] ?? '') ?>" required></div>
        <div class="col-md-4"><label class="form-label fw-semibold">Perfil</label>
          <select name="perfil" class="form-select">
            <option value="operador" <?= ($usuario['perfil'] ?? '')=='operador'?'selected':'' ?>>Operador</option>
            <option value="admin"    <?= ($usuario['perfil'] ?? '')=='admin'?'selected':'' ?>>Administrador</option>
          </select></div>
        <div class="col-md-4"><label class="form-label fw-semibold">Status</label>
          <select name="status" class="form-select">
            <option value="1" <?= ($usuario['status'] ?? 1)==1?'selected':'' ?>>Ativo</option>
            <option value="0" <?= ($usuario['status'] ?? 1)==0?'selected':'' ?>>Inativo</option>
          </select></div>
        <div class="col-md-4"><label class="form-label fw-semibold"><?= $usuario ? 'Nova Senha' : 'Senha *' ?></label>
          <input type="password" name="senha" class="form-control" <?= !$usuario?'required':'' ?>></div>
      </div>
      <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i>Salvar</button>
        <a href="<?= APP_URL ?>/admin/usuarios" class="btn btn-outline-secondary">Cancelar</a>
      </div>
    </form>
  </div>
</div>
