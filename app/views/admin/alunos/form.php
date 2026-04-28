<?php $pageTitle = $aluno ? 'Editar Aluno' : 'Novo Aluno'; ?>
<div class="mb-4">
  <h1 style="font-size:22px;font-weight:800;margin:0;color:var(--primary)">
    <i class="bi bi-person me-2"></i><?= $pageTitle ?>
  </h1>
  <p class="page-subtitle"><a href="<?= APP_URL ?>/admin/alunos">Alunos</a> / <?= $pageTitle ?></p>
</div>
<div class="data-card">
  <div class="p-4">
    <form method="POST" action="<?= $aluno ? APP_URL.'/admin/alunos/'.$aluno['id'].'/salvar' : APP_URL.'/admin/alunos/salvar' ?>">
      <?= csrfField() ?>
      <div class="row g-3">
        <div class="col-md-6"><label class="form-label fw-semibold">Nome *</label>
          <input type="text" name="nome" class="form-control" value="<?= e($aluno['nome'] ?? '') ?>" required></div>
        <div class="col-md-6"><label class="form-label fw-semibold">E-mail *</label>
          <input type="email" name="email" class="form-control" value="<?= e($aluno['email'] ?? '') ?>" required></div>
        <div class="col-md-4"><label class="form-label fw-semibold">CPF</label>
          <input type="text" name="cpf" class="form-control" value="<?= e($aluno['cpf'] ?? '') ?>"></div>
        <div class="col-md-4"><label class="form-label fw-semibold">CRMV</label>
          <input type="text" name="crmv" class="form-control" value="<?= e($aluno['crmv'] ?? '') ?>"></div>
        <div class="col-md-4"><label class="form-label fw-semibold">Telefone</label>
          <input type="text" name="telefone" class="form-control" value="<?= e($aluno['telefone'] ?? '') ?>"></div>
        <div class="col-md-4"><label class="form-label fw-semibold">Data de Nascimento</label>
          <input type="date" name="data_nascimento" class="form-control" value="<?= e($aluno['data_nascimento'] ?? '') ?>"></div>
        <div class="col-md-4"><label class="form-label fw-semibold">Sexo</label>
          <select name="sexo" class="form-select">
            <option value="">—</option>
            <option value="M" <?= ($aluno['sexo'] ?? '')=='M'?'selected':'' ?>>Masculino</option>
            <option value="F" <?= ($aluno['sexo'] ?? '')=='F'?'selected':'' ?>>Feminino</option>
          </select></div>
        <div class="col-md-4"><label class="form-label fw-semibold">Especialidade</label>
          <input type="text" name="especialidade" class="form-control" value="<?= e($aluno['especialidade'] ?? '') ?>"></div>
        <div class="col-md-2"><label class="form-label fw-semibold">CEP</label>
          <input type="text" name="cep" class="form-control" value="<?= e($aluno['cep'] ?? '') ?>"></div>
        <div class="col-md-5"><label class="form-label fw-semibold">Logradouro</label>
          <input type="text" name="logradouro" class="form-control" value="<?= e($aluno['logradouro'] ?? '') ?>"></div>
        <div class="col-md-1"><label class="form-label fw-semibold">Nº</label>
          <input type="text" name="numero" class="form-control" value="<?= e($aluno['numero'] ?? '') ?>"></div>
        <div class="col-md-2"><label class="form-label fw-semibold">Complemento</label>
          <input type="text" name="complemento" class="form-control" value="<?= e($aluno['complemento'] ?? '') ?>"></div>
        <div class="col-md-3"><label class="form-label fw-semibold">Bairro</label>
          <input type="text" name="bairro" class="form-control" value="<?= e($aluno['bairro'] ?? '') ?>"></div>
        <div class="col-md-3"><label class="form-label fw-semibold">Cidade</label>
          <input type="text" name="cidade" class="form-control" value="<?= e($aluno['cidade'] ?? '') ?>"></div>
        <div class="col-md-2"><label class="form-label fw-semibold">UF</label>
          <input type="text" name="estado" maxlength="2" class="form-control" value="<?= e($aluno['estado'] ?? '') ?>"></div>
        <div class="col-md-3"><label class="form-label fw-semibold">Status</label>
          <select name="status" class="form-select">
            <option value="1" <?= ($aluno['status'] ?? 1)==1?'selected':'' ?>>Ativo</option>
            <option value="0" <?= ($aluno['status'] ?? 1)==0?'selected':'' ?>>Inativo</option>
          </select></div>
        <div class="col-md-4"><label class="form-label fw-semibold"><?= $aluno ? 'Nova Senha (deixe em branco p/ manter)' : 'Senha *' ?></label>
          <input type="password" name="senha" class="form-control" <?= !$aluno ? 'required' : '' ?>></div>
      </div>
      <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i>Salvar</button>
        <a href="<?= APP_URL ?>/admin/alunos" class="btn btn-outline-secondary">Cancelar</a>
      </div>
    </form>
  </div>
</div>
