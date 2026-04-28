<?php $pageTitle = 'Meu Perfil'; ?>
<div class="mb-4">
  <h1 style="font-size:22px;font-weight:800;margin:0;color:var(--primary)">
    <i class="bi bi-person-circle me-2"></i>Meu Perfil
  </h1>
</div>
<div class="data-card">
  <div class="p-4">
    <form method="POST">
      <?= csrfField() ?>
      <div class="row g-3">
        <div class="col-md-6"><label class="form-label fw-semibold">Nome *</label>
          <input type="text" name="nome" class="form-control" value="<?= e($usuario['nome']) ?>" required></div>
        <div class="col-md-6"><label class="form-label fw-semibold">E-mail *</label>
          <input type="email" name="email" class="form-control" value="<?= e($usuario['email']) ?>" required></div>
        <div class="col-md-4"><label class="form-label fw-semibold">CPF</label>
          <input type="text" class="form-control" value="<?= e($usuario['cpf'] ?? '') ?>" disabled></div>
        <div class="col-md-4"><label class="form-label fw-semibold">CRMV</label>
          <input type="text" class="form-control" value="<?= e($usuario['crmv'] ?? '') ?>" disabled></div>
        <div class="col-md-4"><label class="form-label fw-semibold">Telefone</label>
          <input type="text" name="telefone" class="form-control" value="<?= e($usuario['telefone'] ?? '') ?>"></div>
        <div class="col-md-6"><label class="form-label fw-semibold">Especialidade</label>
          <input type="text" name="especialidade" class="form-control" value="<?= e($usuario['especialidade'] ?? '') ?>"></div>
        <div class="col-md-6"><label class="form-label fw-semibold">Nova Senha (deixe em branco p/ manter)</label>
          <input type="password" name="senha" class="form-control"></div>
        <div class="col-md-2"><label class="form-label fw-semibold">CEP</label>
          <input type="text" name="cep" class="form-control" value="<?= e($usuario['cep'] ?? '') ?>"></div>
        <div class="col-md-5"><label class="form-label fw-semibold">Logradouro</label>
          <input type="text" name="logradouro" class="form-control" value="<?= e($usuario['logradouro'] ?? '') ?>"></div>
        <div class="col-md-1"><label class="form-label fw-semibold">Nº</label>
          <input type="text" name="numero" class="form-control" value="<?= e($usuario['numero'] ?? '') ?>"></div>
        <div class="col-md-4"><label class="form-label fw-semibold">Bairro</label>
          <input type="text" name="bairro" class="form-control" value="<?= e($usuario['bairro'] ?? '') ?>"></div>
        <div class="col-md-3"><label class="form-label fw-semibold">Cidade</label>
          <input type="text" name="cidade" class="form-control" value="<?= e($usuario['cidade'] ?? '') ?>"></div>
        <div class="col-md-2"><label class="form-label fw-semibold">UF</label>
          <input type="text" name="estado" maxlength="2" class="form-control" value="<?= e($usuario['estado'] ?? '') ?>"></div>
        <div class="col-md-3"><label class="form-label fw-semibold">Complemento</label>
          <input type="text" name="complemento" class="form-control" value="<?= e($usuario['complemento'] ?? '') ?>"></div>
      </div>
      <div class="mt-4">
        <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i>Salvar</button>
      </div>
    </form>
  </div>
</div>
